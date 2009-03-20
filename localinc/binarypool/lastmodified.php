<?php
require_once(dirname(__FILE__) . '/storage.php');
require_once(dirname(__FILE__) . '/views.php');

class binarypool_lastmodified {
    
    private static $modified = array(); 
    
    /**
     * Looks in the URL view for a symlink matching the provided
     * URL and if found, returns the mtime of the link target
     * 
     * If the link points to /dev/null, we have a URL we were unable
     * to download before - if that symlink itself is older than 1 hour
     * we delete it and return 0. If the symlink is younger than hour,
     * we raise an exception and abort. Intended as mechanism to prevent
     * repeated attempts to download the same (non 200) URL in a short
     * time period
     *
     * @param String $bucket
     * @param String $url
     * @return array(time => int Unix timestamp (0 means not found), revalidate => (true|false), cache_age => int) 
     */
    public function getURLLastModified($bucket, $url) {
        $storage = new binarypool_storage($bucket);
        $symlink = binarypool_views::getDownloadedViewPath($bucket, $url);
        return $storage->getURLLastModified($url, $symlink);
    }
    
    /**
     * Wraps getURLLastModified in a static cache
     *
     * @see getURLLastModified
     */
    public function lastModified($bucket, $url) {
        if ( empty(self::$modified[$bucket.$url]) ) {
            self::$modified[$bucket.$url] = $this->getURLLastModified($bucket, $url); 
        }
        return self::$modified[$bucket.$url];
    }
    
    /**
     * Primarily for unit testing - flush any memory of lastmodified
     * urls held in memory 
     */
    public static function resetMemoryCache() {
        self::$modified = array();
    }
    
}