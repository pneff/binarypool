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
        
        if (!is_file($file)) {
            // Input may be an URL, try to fetch it into a local file.
            $tmpfile = tempnam(sys_get_temp_dir(), 'fileinfo');
            file_put_contents($tmpfile, file_get_contents($file));
            if (!is_file($tmpfile)) {
                return array('mime' => '', 'size' => 0);
            }
            
            $info = self::getFileinfo($tmpfile);
            unlink($tmpfile);
            return $info;
        }
        
        $mime = binarypool_mime::getMimeType($file);
        $size = intval(filesize($file));
        $sha1 = sha1_file($file);
        
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