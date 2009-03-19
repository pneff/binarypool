<?php
require_once(dirname(__FILE__) . '/exception.php');
require_once(dirname(__FILE__) . "/render.php");
require_once(dirname(__FILE__) . "/render_image.php");
require_once(dirname(__FILE__) . "/fileinfo.php");

/**
 * Handles writing and reading of asset files which are stored
 * for each file as index.xml.
 */
class binarypool_asset {
    /**
     * Creates a new asset file in memory or loads the referenced
     * asset file and puts the content into the data structures.
     *
     * @param $storage: Storage class used to get the file.
     * @param $file: Relative path of the file inside the storage.
     *               null to create a new asset.
     */
    public function __construct($storage, $file = null) {
        $this->storage = $storage;
        if (is_null($file)) {
            $this->original = null;
            $this->hash = null;
            $this->renditions = array();
            $this->callbacks = array();
            $this->basepath = '';
            $this->locationAbsolute = false;
            $this->created = time();
            $this->expiry = 0;
            $this->type = null;
        } else if (!$storage->isFile($file)) {
            throw new binarypool_exception(112, 500, 'Asset file does not exist: ' . $file);
        } else {
            $this->load($file);
        }
    }
    
    public function setOriginal($file) {
        $this->original = $file;
        $info = binarypool_fileinfo::getFileinfo($file);
        $this->hash = $info['hash'];
    }
    
    public function getOriginal() {
        return $this->original;
    }
    
    public function getType() {
        return $this->type;
    }
    
    public function setType($type) {
        $this->type = $type;
    }
    
    /**
     * Returns the content hash of the original file.
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * Returns a UNIX timestamp for the date and time when this
     * asset file was originally created.
     */
    public function getCreated() {
        return $this->created;
    }
    
    /**
     * Returns a UNIX timestamp for the date and time when this
     * asset file will expire.
     */
    public function getExpiry() {
        return $this->expiry;
    }
    
    /**
     * Sets a UNIX timestamp for the date and time when this
     * asset file will expire.
     */
    public function setExpiry($expiry) {
        $this->expiry = intval($expiry);
    }
    
    /**
     * Sets a rendition identified by the rendition name.
     *
     * @param $rendition: Name of the rendition.
     * @param $file: File path or URL for this rendition. The path must exist.
     */
    public function setRendition($rendition, $file) {
        $this->renditions[$rendition] = $file;
    }

    /**
     * Returns a rendition identified by the rendition name.
     *
     * @param $rendition: Name of the rendition.
     * @return: Absolute path to the rendition file.
     */
    public function getRendition($rendition) {
        return $this->renditions[$rendition];
    }
    
    /**
     * Returns an associative array of renditions. The key
     * is the rendition name, the value is an absolute
     * path to the image.
     */
    public function getRenditions() {
        return $this->renditions;
    }
    
    /**
     * Adds a new callback. Callbacks are asked for permission
     * when it comes to deleting this asset.
     *
     * @param $callback: URL which returns a response in the Binary Pool
     *                   permission response format.
     */
    public function addCallback($callback) {
        if (!in_array($callback, $this->callbacks)) {
            array_push($this->callbacks, $callback);
        }
    }
    
    /**
     * Returns an array with all configured callbacks.
     */
    public function getCallbacks() {
        return $this->callbacks;
    }
    
    /**
     * Set the base path. Will be prepended to all paths
     * included in the asset file and should thus be
     * relative to the Binary Pool root.
     *
     * @param $path: Base path where the files included in this
     *               asset file are or will be located.
     * @param $absolute: Whether the location is to be stored
     *                   as an absolute path in the output XML.
     */
    public function setBasePath($path, $absolute = false) {
        // Make sure the base path always has a trailing slash
        if (substr($path, -1, 1) != '/') {
            $path .= '/';
        }
        $this->basepath = $path;
        $this->locationAbsolute = $absolute;
    }
    
    /**
     * Returns the asset's relative path to the binary pool
     * home. This is the closest thing to an ID we have in
     * the binary pool system.
     */
    public function getBasePath() {
        return $this->basepath;
    }
    
    /**
     * Returns an XML representation of the asset file based on the
     * status set using setOriginal and setRendition calls.
     */
    public function getXML() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<registry version="3.0">';
        
        // Attributes of the registry
        $xml .= '<created>' . htmlspecialchars($this->created) . '</created>';
        $xml .= '<expiry>' . htmlspecialchars($this->expiry) . '</expiry>';
        $xml .= '<basepath>' . htmlspecialchars($this->basepath) . '</basepath>';
        foreach ($this->callbacks as $callback) {
            $xml .= '<callback>' . htmlspecialchars($callback) . '</callback>';
        }
        
        $xml .= '<items>';
        if (! is_null($this->original)) {
            $xml .= $this->getItem($this->original, null);
            $xml .= "\n";
        }
        
        foreach ($this->renditions as $renditionName => $renditionFile) {
            $xml .= $this->getItem($renditionFile, $renditionName);
            $xml .= "\n";
        }
        
        $xml .= '</items></registry>';
        
        return $xml;
    }
    
    /**
     * Returns the XML representation of a single item.
     *
     * @param $file: Absolute file name on the disk for this item.
     * @param $rendition: Rendition name. null if this is the original.
     */
    private function getItem($file, $rendition) {
        $fproxy = new binarypool_fileobject($file);
        if ( !$fproxy->exists() ) {
            throw new binarypool_exception(102, 404, "Referenced file in asset does not exist: $file");
        }

        $fileinfo = binarypool_fileinfo::getFileinfo($file);
        $mime = $fileinfo['mime'];
        $size = $fileinfo['size'];
        $hash = $fileinfo['hash'];
        $type = is_null($this->type) ?
            binarypool_render::getType($mime) :
            $this->type;
        $info = binarypool_mime::getImageSize($file, $mime, $type);
        $isRendition = is_null($rendition) ? 'false' : 'true';
        $isLandscape = ($info['width'] > $info['height']) ? 'true' : 'false';
        
        $xml = '<item type="' . $type . '" isRendition="' . $isRendition . '">';
        
        $xml .= '<webobject isVisual="true" isAudioOnly="false" unit="' . htmlspecialchars($info['unit']) . '">';
        $xml .= '<objectWidth>' . $info['width'] . '</objectWidth>';
        $xml .= '<objectHeight>' . $info['height'] . '</objectHeight>';
        $xml .= '</webobject>';

        $xml .= '<imageinfo isLandscape="' . $isLandscape . '" unit="' . htmlspecialchars($info['unit']) . '">';
        $xml .= '<width>' . $info['width'] . '</width>';
        $xml .= '<height>' . $info['height'] . '</height>';
        $xml .= '</imageinfo>';
        
        if ($isRendition) {
            $xml .= '<rendition>' . htmlspecialchars($rendition) . '</rendition>';
        }
        
        if ($this->locationAbsolute) {
            $xml .= '<location absolute="true">' . $file . '</location>';
        } else {
            $xml .= '<location>' . htmlspecialchars($this->basepath . basename($file)) . '</location>';
        }
        $xml .= '<importsource />';
        $xml .= '<mimetype>' . htmlspecialchars($mime) . '</mimetype>';
        $xml .= '<size>' . $size . '</size>';
        $xml .= '<hash>' . htmlspecialchars($hash) . '</hash>';
        
        $xml .= '</item>';
        
        return $xml;
    }
    
    /**
     * Load asset file into memory.
     *
     * @param $file: Filename to load data from.
     */
    private function load($file) {
        // Load document into RAM, do checks
        $dom = new DOMDocument();
        if ( !$dom->loadXML($this->storage->getFile($file)) ) {
            throw new binarypool_exception(113, 500, "Invalid asset file: $file");
        }
        
        $xp = new DOMXPath($dom);
        if ($this->getXPathValue($xp, '/registry/@version') != '3.0') {
            throw new binarypool_exception(113, 500, "Invalid asset file: $file");
        }

        // Get asset properties
        $this->created = intval($this->getXPathValue($xp, '/registry/created'));
        $this->expiry = intval($this->getXPathValue($xp, '/registry/expiry'));
        
        // Get callbacks
        $this->callbacks = array();
        $res = $xp->query('/registry/callback');
        foreach ($res as $item) {
            $this->addCallback($item->nodeValue);
        }
        
        // Get base path
        $assetDirectory = dirname($file);
        $this->basepath = $this->getXPathValue($xp, '/registry/basepath');
        if (! $this->basepath) {
            $path = $this->getXPathValue($xp, '/registry/items/item/location');
            $this->basepath = substr($path, 0, strrpos($path, '/')+1);
        }
        
        // Load renditions & original
        $this->renditions = array();
        $nodes = $xp->query('/registry/items/item');
        foreach ($nodes as $node) {
            $isOriginal = ($node->getAttribute('isRendition') == 'false');
            $renditionName = $xp->query('rendition', $node)->item(0)->nodeValue;
            
            $renditionLocationNode = $xp->query('location', $node)->item(0);
            $this->locationAbsolute = ($renditionLocationNode->getAttribute('absolute') == 'true');
            $renditionLocation = $renditionLocationNode->nodeValue;
            if (! $this->locationAbsolute) {
                $renditionLocation = substr($renditionLocation, strrpos($renditionLocation, '/'));
                $renditionLocation = $assetDirectory . $renditionLocation;
                $renditionLocationAbs = $this->storage->absolutize($renditionLocation);
            } else {
                $renditionLocationAbs = $renditionLocation;
            }
            
            $hashNodes = $xp->query('hash', $node);
            $hash = '';
            if ($hashNodes->length == 0) {
                $fileinfo = binarypool_fileinfo::getFileinfo($renditionLocationAbs);
                $hash = $fileinfo['hash'];
            } else {
                $hash = $hashNodes->item(0)->nodeValue;
            }
            
            $fileinfo = array(
                'mime' => $xp->query('mimetype', $node)->item(0)->nodeValue,
                'size' => intval($xp->query('size', $node)->item(0)->nodeValue),
                'hash' => $hash,
            );
            binarypool_fileinfo::setCache($renditionLocationAbs, $fileinfo);
            
            if ($isOriginal) {
                $this->setOriginal($renditionLocationAbs);
                $this->type = $node->getAttribute('type');
            } else {
                $this->setRendition($renditionName, $renditionLocationAbs);
            }
        }
    }
    
    /**
     * Returns the first nodeValue from the given XPath
     * query.
     */
    private function getXPathValue($xp, $query) {
        $res = $xp->query($query);
        if (!$res || $res->length == 0) {
            return null;
        }
        
        return $res->item(0)->nodeValue;
    }
}
