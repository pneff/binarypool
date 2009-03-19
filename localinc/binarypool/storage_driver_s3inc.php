<?php
/**
 * A storage implementation which uses local files in read-only
 * mode and S3 files for read/write part.
 *
 * If a file is available on the local file system, that is used,
 * otherwise the S3 version is tried.
 */
class binarypool_storage_driver_s3inc extends binarypool_storage_driver {
    public function __construct($cfg, $client = null, $cache = null) {
        $this->s3 = new binarypool_storage_driver_s3($cfg, $client, $cache);
        $this->local = new binarypool_storage_driver_file();
    }

    public function absolutize($file) {
        if ($this->s3->fileExists($file)) {
            return $this->s3->absolutize($file);
        } else {
            return $this->local->absolutize($file);
        }
    }

    public function save($local_file, $remote_file) {
        return $this->s3->save($local_file, $remote_file);
    }

    public function getRenditionsDirectory($dir) {
        return $this->s3->getRenditionsDirectory($dir);
    }

    public function saveRenditions($renditions, $dir) {
        return $this->s3->saveRenditions($renditions, $dir);
    }
    
    public function rename($source, $target) {
        if ($this->local->fileExists($source)) {
            $this->local->rename($source, $target);
        }
        if ($this->s3->fileExists($source)) {
            $this->s3->rename($source, $target);
        }
        return true;
    }
    
    public function fileExists($file) {
        return ($this->local->fileExists($file) ||
                $this->s3->fileExists($file));
    }
    
    public function isFile($file) {
        if ($this->local->fileExists($file) && $this->local->isFile($file)) {
            return true;
        }
        if ($this->s3->fileExists($file) && $this->s3->isFile($file)) {
            return true;
        }
        return false;
    }
    
    public function isDir($file) {
        if ($this->local->fileExists($file) && $this->local->isDir($file)) {
            return true;
        }
        if ($this->s3->fileExists($file) && $this->s3->isDir($file)) {
            return true;
        }
        return false;
    }
    
    public function getFile($file) {
        if ($this->s3->fileExists($file)) {
            if (strrpos($file, '/index.xml') === strlen($file) - 10) {
                $file = $this->getTransformedIndexFile($file);
                return file_get_contents($file);
            } else {
                return $this->s3->getFile($file);
            }
        } else {
            return $this->local->getFile($file);
        }
    }
    
    public function sendFile($file) {
        if ($this->s3->fileExists($file)) {
            if (strrpos($file, '/index.xml') === strlen($file) - 10) {
                $file = $this->getTransformedIndexFile($file);
                readfile($file);
            } else {
                return $this->s3->sendFile($file);
            }
        } else {
            return $this->local->sendFile($file);
        }
    }
    
    public function isAbsoluteStorage() {
        return false;
    }
    
    public function listDir($dir) {
        $retval = array();
        $retval = array_merge($retval, $this->local->listDir($dir));
        $retval = array_merge($retval, $this->s3->listDir($dir));
        return $retval;
    }
    
    public function unlink($file) {
        if ($this->local->fileExists($file)) {
            $this->local->unlink($file);
        }
        if ($this->s3->fileExists($file)) {
            $this->s3->unlink($file);
        }
    }
    
    public function symlink($target, $link) {
        $this->s3->symlink($target, $link);
    }
    
    public function relink($target, $link) {
        $this->s3->relink($target, $link);
    }
    
    public function getURLLastModified($url, $symlink, $bucket) {
        if ($this->s3->fileExists($symlink . '.link')) {
            return $this->s3->getURLLastModified($url, $symlink, $bucket);
        } else if ($this->local->fileExists($symlink)) {
            return $this->local->getURLLastModified($url, $symlink, $bucket);
        } else {
            return $this->s3->getURLLastModified($url, $symlink, $bucket);
        }
    }
    
    public function getAssetForLink($bucket, $symlink) {
        if ($this->s3->fileExists($symlink . '.link')) {
            return $this->s3->getAssetForLink($bucket, $symlink);
        } else {
            return $this->local->getAssetForLink($bucket, $symlink);
        }
    }
    
    /**
     * Download the asset file and change all rendition locations to absolute
     * values.
     *
     * This is necessary for the transformation stage to make sure that we
     * can request the asset files from the binarypool server but serve
     * them from S3 directly.
     */
    protected function getTransformedIndexFile($file) {
        $url = $this->s3->absolutize($file);
        $fproxy = new binarypool_fileobject($url);
        if ( !$fproxy->exists() ) {
            return null;
        }
        
        $dom = new DOMDocument();
        $dom->load($fproxy->file);
        $xp = new DOMXPath($dom);
        $locs = $xp->query('/registry/items/item/location');
        foreach ($locs as $loc) {
            if (!$loc->hasAttribute('absolute') || $loc->getAttribute('absolute') !== 'true') {
                if ($this->s3->fileExists($loc->nodeValue)) {
                    $loc->setAttribute('absolute', 'true');
                    $loc->nodeValue = $this->s3->absolutize($loc->nodeValue);
                }
            }
        }
        file_put_contents($fproxy->file, $dom->saveXML());
        return $fproxy->file;
    }
}
