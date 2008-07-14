<?php
require_once(dirname(__FILE__) . '/exception.php');
require_once(dirname(__FILE__) . '/asset.php');
require_once(dirname(__FILE__) . '/render.php');
require_once(dirname(__FILE__).  '/config.php');
require_once(dirname(__FILE__).  '/validate.php');
require_once(dirname(__FILE__).  '/mime.php');
require_once(dirname(__FILE__).  '/storage_driver_file.php');
require_once(dirname(__FILE__).  '/storage_driver_s3.php');

/**
 * Handles storing of files in the binary pool. The repository
 * is split in buckets which may have different configurations.
 *
 * The following work falls in the scope of this class:
 *    - Storing pictures (originals and associated renditions)
 *    - Storing an index file (called asset files)
 *    - Organising the file system inside each bucket
 *
 * Outside of this class nobody should try to understand or
 * re-implement the file naming schema.
 */
class binarypool_storage {
    protected $bucketName = null;
    protected $bucketConfig = null;
    
    public function __construct($bucket) {
        $buckets = binarypool_config::getBuckets();
        if (! isset($buckets[$bucket])) {
            throw new binarypool_exception(100, 404, "Bucket not defined: $bucket");
        }
        
        $this->bucketName = $bucket;
        $this->bucketConfig = $buckets[$bucket];
        $this->storage = $this->getStorage($this->bucketConfig);
        $this->assetStorage = $this->getStorage(null);
    }
    
    /**
     * Returns the directory to store the given file
     * and everything that belongs to it.
     */
    private function getDirectory($file) {
        $sha1 = sha1_file($file);
        return $this->bucketName . '/' . $this->hashMapper($sha1) . '/';
    }
    
    /**
     * Returns a partial directory name for the given SHA1
     * hash.
     */
    private function hashMapper($hash) {
        return substr($hash, 0, 2) . '/' . $hash;
    }
    
    /**
     * Return the absolute path to the given file or path.
     * The file does not have to exist yet.
     */
    public function absolutize($file) {
        return $this->storage->absolutize($file);
    }
    
    /**
     * Saves a file and returns a path to the asset file.
     */
    public function save($type, $files) {
        $this->validateSaveParams($type, $files);
        
        $origFile = $files['_']['file'];
        $origFilename = isset($files['_']['filename']) ? $files['_']['filename'] : '';
        
        $dir = $this->getDirectory($origFile);
        $originalFile = $this->saveOriginalFile($dir, $origFile, $origFilename);
        
        $assetFile = $dir . 'index.xml';
        $renditions = $this->saveRenditions($type, $dir, $origFile, $files);
        
        return $this->createAssetFile($assetFile, $dir, $originalFile, $renditions);
    }

    private function createAssetFile($assetFile, $dir, $originalFile, $renditions) {
        $assetFileAbs = $this->assetStorage->absolutize($assetFile);

        $asset = null;
        if (file_exists($assetFileAbs)) {
            // Load existing asset file
            $asset = new binarypool_asset($assetFileAbs);
        } else {
            $asset = new binarypool_asset();
        }
        // If the asset file and the target files point to different
        // locations, then store the locations as absolute values in the
        // asset file
        $storeAbsolute = ($this->assetStorage->absolutize($assetFile) != $this->storage->absolutize($assetFile));
        $asset->setBasePath($dir, $storeAbsolute);
        
        $asset->setOriginal($this->storage->absolutize($originalFile));
        foreach ($renditions as $rendition => $filename) {
            $asset->setRendition($rendition, $this->absolutize($filename));
        }
        
        // Expiry date
        if (! isset($this->bucketConfig['ttl'])) {
            throw new BinaryPoolException(116, 500, 'Bucket does not have a defined TTL: ' . $this->bucketName);
        }
        $asset->setExpiry(time() + (intval($this->bucketConfig['ttl']) * 24 * 60 * 60));
        
        // Save
        if (! file_exists(dirname($assetFileAbs))) {
            mkdir(dirname($assetFileAbs), 0755, true);
        }
        file_put_contents($assetFileAbs, $asset->getXML());
        
        // Done
        return $assetFile;
    }
    
    private function validateSaveParams($type, $files) {
        # Validate paths and make sure filename index always exists
        foreach ($files as $rendition => $file) {
            if (! file_exists($file['file'])) {
                throw new binarypool_exception(103, 404, "File to save in binary pool does not exist: " . $file['file']);
            }
            
            binarypool_validate::validate($type, $this->bucketName, $file['file']);
        }
    }
    
    private function saveRenditions($type, $dir, $originalFile, $files) {
        return array_merge(
            $this->generateRenditions($type, $dir, $originalFile, $files),
            $this->saveUploadedRenditions($dir, $files));
    }
    
    private function generateRenditions($type, $dir, $originalFile, $files) {
        $outputDir = $this->storage->getRenditionsDirectory($dir);
        $renditions = binarypool_render::render($type, $this->bucketName,
                $originalFile, $outputDir,
                array_keys($files));
        return $this->storage->saveRenditions($renditions, $dir);
    }

    private function saveUploadedRenditions($dir, $files) {
        $renditions = array();
        
        foreach ($files as $rendition => $file) {
            if ($rendition == '_') {
                continue;
            }
            
            $filename = isset($file['filename']) ? $file['filename'] : '';
            $filename = binarypool_mime::fixExtension($file['file'], $basename);
            if ($filename == 'index.xml') {
                $filename = 'index-' . $rendition . '.xml';
            }
            $this->storage->save($file['file'], $dir . $filename);
            $renditions[$rendition] = $dir . $filename;
        }
        
        return $renditions;
    }
    
    /**
     * Deletes a file. This method should not be exposed over the
     * API as it does no checks whatsoever about whether a binary
     * is still used or not.
     *
     * This call should be wrapped inside some expiry policy which
     * takes care of those issues.
     *
     * @param $asset: Relative path to the asset file.
     */
    public function delete($asset) {
        // Load asset from file
        $assetObj = new binarypool_asset($this->assetStorage->absolutize($asset));
        $basepath = $assetObj->getBasePath();
        
        $date = date('Y/m/d');
        $trashDir = 'Trash/' . $date . '/' . $basepath;
        $this->storage->rename($basepath, $trashDir);
        $this->assetStorage->rename($basepath, $trashDir);
    }

    /**
     * Returns the relative path to an asset file by it's SHA1 hash.
     */
    public function getAssetBySha1($hash) {
        $directory = $this->bucketName . '/' . $this->hashMapper($hash) . '/';
        $file = $directory . 'index.xml';
        if (! file_exists($this->assetStorage->absolutize($file))) {
            throw new binarypool_exception(115, 404, "File does not exist: $hash");
        }
        
        return $file;
    }
    
    /**
     * Saves the file as original.
     */
    private function saveOriginalFile($dir, $file, $basename) {
        $filename = binarypool_mime::fixExtension($file, $basename);
        if (empty($filename)) {
            $filename = 'original';
        }
        if ($filename == 'index.xml') {
            $filename = 'index-document.xml';
        }
        
        $this->storage->save($file, $dir . $filename);
        return $dir . $filename;
    }
    
    /**
     * Returns the storage object for the given bucket.
     */
    private function getStorage($bucketConfig) {
        if (!is_null($bucketConfig) && isset($bucketConfig['storage'])) {
            $cls = 'binarypool_storage_driver_' . $bucketConfig['storage']['backend'];
            return new $cls($bucketConfig['storage']);
        } else {
            return new binarypool_storage_driver_file();
        }
    }
}
?>
