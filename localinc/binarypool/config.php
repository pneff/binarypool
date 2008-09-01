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
    private static $paths = array();
    private static $badUrlExpiry = 3600;
    private static $cacheRevalidate = 86400; 
    private static $useragent = 'Binary Pool/1.0';
    
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
     * Returns a path for a utility.
     */
    public static function getUtilityPath($utility) {
        if (! self::$loaded) self::load();
        if (isset(self::$paths[$utility])) {
            return self::$paths[$utility];
        } else {
            return null;
        }
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
     * Time in seconds after which downloaded content should be
     * re-validated (via conditional get / If-Modified-Since)
     *
     * @return int seconds
     */
    public static function getCacheRevalidate() {
        if (! self::$loaded) self::load();
        return self::$cacheRevalidate;
    }
    
    /**
     * Returns a Useragent string for the binary "fetcher"
     * used to pull new images via HTTP 
     */
    public static function getUseragent() {
        if (! self::$loaded) self::load();
        return self::$useragent;
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
        
        self::$paths = $PATHS;
        self::$badUrlExpiry = $BADURLEXPIRY;
        self::$cacheRevalidate = $CACHEREVALIDATE;
        self::$useragent = $USERAGENT;
    }
}
?>
