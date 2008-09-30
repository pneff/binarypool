<?php
require_once(dirname(__FILE__) . '/storage_driver.php');

/**
 * A storage implementation to save files to the local file system.
 */
class binarypool_storage_driver_file extends binarypool_storage_driver {
    public function __construct($root = null) {
        if (is_null($root)) {
            if (binarypool_config::getRoot() == '') {
                throw new binarypool_exception(107, 500, "Binary Pool path is not configured.");
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
        $this->clearstatcache();
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
        $this->clearstatcache();
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
        $this->clearstatcache();
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
    
    public function fileExists($file) {
        return file_exists($this->absolutize($file));
    }
    
    public function isFile($file) {
        return is_file($this->absolutize($file));
    }
    
    public function isDir($file) {
        return is_dir($this->absolutize($file));
    }
    
    public function getFile($file) {
        return file_get_contents($this->absolutize($file));
    }
    
    public function sendFile($file) {
        readfile($this->absolutize($file));
    }
    
    public function isAbsoluteStorage() {
        return false;
    }
    
    public function getURLLastModified($url, $symlink) {
        $this->clearstatcache();
        if (!$this->fileExists($symlink)) {
            return array('time' => 0, 'revalidate' => true, 'cache_age' => 0);
        }
        
        $symlinkAbs = $this->absolutize($symlink);
        $stat = lstat($symlinkAbs);
        $now = time();
        
        if (readlink($symlinkAbs) == '/dev/null') {
            $failed_time = $now - $stat['mtime'];
            if ($failed_time > binarypool_config::getBadUrlExpiry()) {
                unlink($symlink);
                return array('time' => 0, 'revalidate' => true, 'cache_age' => $failed_time);
            }
            
            $failed_nextfetch = ($stat['mtime'] + binarypool_config::getBadUrlExpiry()) - $now;
            throw new binarypool_exception(122, 400, "File download failed $failed_time seconds ago. Re-fetching allowed in next time in $failed_nextfetch seconds: $url");
        }
        
        $cache_age = $now - $stat['mtime'];
        $revalidate = false;
        if ($cache_age > binarypool_config::getCacheRevalidate()) {
            $revalidate = true;
        }
        
        return array(
            'time' => filemtime($symlinkAbs),
            'revalidate' => $revalidate,
            'cache_age' => $cache_age); 
    }
    
    public function listDir($dir) {
        $files = array();
        $absDir = $this->absolutize($dir);
        if (is_dir($absDir)) {
            if ($dirhandle = opendir($absDir)) {
                while (($file = readdir($dirhandle)) !== false) {
                    if ($file != '.' && $file != '..') {
                        $assetFile = $dir . '/' . $file . '/index.xml';
                        if ($this->isFile($assetFile)) {
                            $asset = new binarypool_asset($this, $assetFile);
                            array_push($files, $asset->getBasePath() . 'index.xml');
                        }
                    }
                }
                closedir($dirhandle);
            }
        }
        return $files;
    }
    
    public function unlink($file) {
        unlink($this->absolutize($file));
    }
    
    public function symlink($target, $link, $refresh = false) {
        $this->clearstatcache();
        $link = $this->absolutize($link);
        
        if (! file_exists(dirname($link))) {
            mkdir(dirname($link), 0755, true);
        }
        
        if (! file_exists($link)) {
            symlink($target, $link);
        } else if ($refresh) {
            // "touch" the symlink
            $tmplink = sprintf("/tmp/%s%s", sha1($link), microtime(True));
            symlink($target, $tmplink);
            rename($tmplink, $link);
        }
    }
    
    public function getAssetForLink($bucket, $symlink) {
        return str_replace('../..', $bucket, readlink($this->absolutize($symlink))). '/index.xml';
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
    
    /**
     * Clear the stat cache - solves issues in conjunction with
     * PHP-FCGI and the downloaded view, when it comes to flushing the cache
     */
    private function clearstatcache() {
        static $cleared = false;
        if ( !$cleared ) {
            clearstatcache();
            $cleared = true;
        }
    }
}
