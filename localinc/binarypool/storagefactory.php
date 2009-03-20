<?php
require_once(dirname(__FILE__).  '/storage.php');

/**
 * Factory to create binarypool_storage objects. Added to allow
 * mocking of binarypool_storage
 */
class binarypool_storagefactory {
    
    private $storages = array();
    
    /**
     * @param $bucket string: bucket name
     */
    public function getStorage($bucket) {
        if ( !array_key_exists($bucket, $this->storages) ) {
            $this->storages[$bucket] = new binarypool_storage($bucket); 
        }
        return $this->storages[$bucket];
    }
    
}