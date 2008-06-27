<?php
require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/asset.php');

/**
 * Read-only access to the binarypool file system.
 */
class binarypool_browser {
    /**
     * Returns all items which expired in the last seven days
     * including today.
     *
     * All the returned file names are relative paths.
     */
    public static function getExpired($bucket) {
        $files = array();
        
        for ($day = 0; $day < 100; $day++) {
            // Date directory for given day
            $dateDir = date('Y/m/d', time() - ($day * 24 * 60 * 60));
            
            // Get all asset files which expired in those days
            $absDateDir = binarypool_config::getRoot() . $bucket . '/expiry/' . $dateDir;
            if (is_dir($absDateDir)) {
                if ($dirhandle = opendir($absDateDir)) {
                    while (($file = readdir($dirhandle)) !== false) {
                        if ($file != '.' && $file != '..') {
                            $asset = new binarypool_asset($absDateDir . '/' . $file . '/index.xml');
                            array_push($files, $asset->getBasePath() . 'index.xml');
                        }
                    }
                    closedir($dirhandle);
                }
            }
        }
        
        return $files;
    }
}
?>
