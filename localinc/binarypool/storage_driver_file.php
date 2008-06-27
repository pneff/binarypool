<?php
require_once(dirname(__FILE__) . '/storage_driver.php');

/**
 * A storage implementation to save files to the local file system.
 */
class binarypool_storage_driver_file extends binarypool_storage_driver {
    public function __construct($root = null) {
        if (is_null($root)) {
            if (binarypool_config::getRoot() == '') {
                throw new binarypool_exception(107, 500, "Binarypool path is not configured.");
            }
            $root = binarypool_config::getRoot();
        }
        if ($root[strlen($root)-1] !== '/') {
            $root .= '/';
        }
        
        $this->root = $root;
    }

    public function absolutize($file) {
        return $this->root . $file;
    }

    public function save($local_file, $remote_file) {
        $targetFile = $this->absolutize($remote_file);
        
        $dir = dirname($targetFile);
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!file_exists($dir)) {
            throw new binarypool_exception(104, 500, "Could not create directory to hold uploaded file: $absoluteDir");
        }
        
        copy($local_file, $targetFile);
        if (!file_exists($targetFile)) {
            throw new binarypool_exception(105, 500, "Could not copy file to its final destination: $targetFile");
        }
        return true;
    }

    public function getRenditionsDirectory($dir) {
        $dir = $this->absolutize($dir);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!file_exists($dir)) {
            throw new binarypool_exception(104, 500, "Could not create directory to hold renditions file: $dir");
        }
        if ($dir[strlen($dir)-1] !== '/') {
            $dir .= '/';
        }
        return $dir;
    }

    public function saveRenditions($renditions, $dir) {
        if ($dir[strlen($dir)-1] !== '/') {
            $dir .= '/';
        }

        $retval = array();
        foreach ($renditions as $name => $file) {
            $retval[$name] = $dir . basename($file);
        }
        return $retval;
    }
    
    public function rename($source, $target) {
        $sourceAbs = $this->absolutize($source);
        $targetAbs = $this->absolutize($target);
        if (!file_exists($sourceAbs)) {
            return;
        }
        
        $targetParent = dirname($targetAbs);
        if (!file_exists($targetParent)) {
            mkdir($targetParent, 0755, true);
        }
        if (!file_exists($targetAbs)) {
            rename($sourceAbs, $targetAbs);
        }
        
        // Delete to make sure it's gone
        if (file_exists($sourceAbs)) {
            $this->deltree($sourceAbs);
        }
    }

    /**
     * Removes a directory recursively.
     */
    private function deltree($path) {
        if (!is_link($path) && is_dir($path)) {
            $entries = scandir($path);
            foreach ($entries as $entry) {
                if ($entry != '.' && $entry != '..') {
                    $this->deltree($path . DIRECTORY_SEPARATOR . $entry);
                }
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }
}
