<?php
/**
 * Stores configuration of the binary pool.
 *
 * This mostly concerns the configuration of buckets.
 */
class binarypool_config {
    private static $loaded = false;
    private static $buckets = array();
    private static $root = '/tmp/binarypool';
    private static $logpath = '/tmp/binarypool-log';
    private static $paths = array();
    private static $badUrlExpiry = 3600; 
    
    /**
     * Associative array of all configured buckets.
     */
    public static function getBuckets() {
        if (! self::$loaded) self::load();
        return self::$buckets;
    }
    
    /**
     * Absolute path on the file system to the binary pool.
     */
    public static function getRoot() {
        if (! self::$loaded) self::load();
        return self::$root;
    }
    
    /**
     * Returns the directory for logging activity to.
     */
    public static function getLogPath() {
        if (! self::$loaded) self::load();
        return self::$logpath;
    }
    
    /**
     * Returns a path for a utility.
     */
    public static function getUtilityPath($utility) {
        if (! self::$loaded) self::load();
        return self::$paths[$utility];
    }
    
    /**
     * Returns the duration in seconds, where a URL flagged as bad
     * continues to be regarded as bad. After this time, the flag is
     * removed 
     */
    public static function getBadUrlExpiry() {
        if (! self::$loaded) self::load();
        return self::$badUrlExpiry;
    }
    
    /**
     * Load settings from the config file.
     */
    private static function load() {
        // Read correct config file based on environment
        $env = '';
        if (isset($_SERVER['BINARYPOOL_CONFIG'])) {
            $env = $_SERVER['BINARYPOOL_CONFIG']; 
        }
        $filename = API_PROJECT_DIR . "/conf/binarypool.php";
        if ($env != '' && file_exists($filename)) {
            $filename = API_PROJECT_DIR . '/conf/binarypool-' . $env . '.php';
        }
        include($filename);
        
        // Get values
        self::$buckets = $BUCKETS;
        self::$root = $ROOT;
        if (!file_exists(self::$root)) {
            mkdir(self::$root);
            if (!file_exists(self::$root)) {
                throw new binarypool_exception(107, 500, "Binary Pool path is not available.");
            }
        }
        self::$root = realpath(self::$root);
        if (self::$root[strlen(self::$root)-1] !== '/') {
            self::$root .= '/';
        }
        
        self::$logpath = $LOGPATH;
        self::$paths = $PATHS;
    }
}
?>
