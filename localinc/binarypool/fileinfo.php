<?php
/**
 * Returns information about files. Does in-memory caching.
 */
class binarypool_fileinfo {
    protected static $fileinfoCache = array();
    
    /**
     * Returns information about a file. Handles URLs as well as local files.
     *
     * @param $file: A file path or URL.
     */
    public static function getFileinfo($file) {
        
        if (isset(self::$fileinfoCache[$file])) {
            return self::$fileinfoCache[$file];
        }
        
        $mime = null;
        $size = null;
        $sha1 = null;
        
        $fproxy = new binarypool_fileobject($file);
        if ( $fproxy->exists() ) {
            $mime = binarypool_mime::getMimeType($fproxy->file);
            $size = intval(filesize($fproxy->file));
            $sha1 = sha1_file($fproxy->file);
        }
        
        $info = array('mime' => $mime, 'size' => $size, 'hash' => $sha1);
        self::$fileinfoCache[$file] = $info;
        return $info;
    }

    /**
     * Registers file information for a given path. This can be used to save
     * extra network roundtrips.
     *
     * This is done automatically by getFileinfo.
     */
    public static function setCache($file, $info) {
        self::$fileinfoCache[$file] = $info;
    }
}
