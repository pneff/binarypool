<?php
require_once(dirname(__FILE__).'/../../binarypool/asset.php');
require_once(dirname(__FILE__).'/../../binarypool/views.php');
require_once(dirname(__FILE__).'/../../binarypool/exception.php');
require_once(dirname(__FILE__).'/../../binarypool/config.php');

class api_command_touch extends api_command_base {
    protected function execute() {
        $uri = $this->request->getPath();
        
        if (!$this->touch($this->bucket, $uri)) {
            throw new binarypool_exception(115, 404, "File not found: " . $uri);
        }
        $this->response->send();
        $this->ignoreView = true;
    }
    
    protected function touch($bucket, $uri) {
        $storage = new binarypool_storage($bucket);
        $assetFile = $uri;
        
        if (! $storage->isFile($assetFile)) {
            $assetFile .= '/index.xml';
            if (! $storage->isFile($assetFile)) {
                return false;
            }
        }
        
        // Get TTL from request
        $buckets = binarypool_config::getBuckets();
        $ttl = $buckets[$bucket]['ttl'];
        if ($this->request->getParam('TTL')) {
            $newttl = intval($this->request->getParam('TTL'));
            if ($newttl <= $ttl) {
                // Don't allow higher TTL than bucket configuration
                $ttl = $newttl;
            }
        }
        
        // Set TTL
        $oldAsset = $storage->getAssetObject($assetFile);
        $asset = $storage->getAssetObject($assetFile);
        $asset->setExpiry(time() + ($ttl * 24 * 60 * 60));
        $storage->saveAsset($asset, $assetFile);
        
        // Update views
        binarypool_views::updated($bucket, $assetFile, $oldAsset);
        $this->setResponseCode(204);
        return true;
    }
}
