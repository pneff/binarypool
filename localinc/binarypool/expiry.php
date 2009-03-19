<?php
require_once(dirname(__FILE__) . '/asset.php');

/**
 * Implements the expiry protocol.
 *
 * Asset files optionally contain a list of callbacks. These callbacks
 * are asked if this binary can be deleted. Based on the response
 * a decision is taken.
 */
class binarypool_expiry {
    /**
     * Checks if the asset file is an expired resource.
     */
    public static function isExpired($bucket, $asset) {
        $storage = new binarypool_storage($bucket);
        $obj = $storage->getAssetObject($asset);
        
        if ($obj->getExpiry() > time()) {
            // Item expires in the future
            return false;
        }
        
        // Check callbacks
        foreach ($obj->getCallbacks() as $callback) {
            $status = self::getCallbackOpinion($callback);
            if ($status === FALSE) {
                // Permission not granted
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Ask the given callback for permission to delete the asset.
     *
     * Function returns TRUE if the callback gives permission to delete,
     * FALSE otherwise.
     */
    private static function getCallbackOpinion($url) {
        // Don't output warnings about not being able to open file
        $prevlevel = error_reporting(1);
        $contents = file_get_contents($url);
        error_reporting($prevlevel);
        
        if ($contents === 'EXPIRED') {
            return TRUE;
        }
        return FALSE;
    }
}

