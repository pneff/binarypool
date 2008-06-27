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
        $assetFile = $uri;
        if (!file_exists(binarypool_config::getRoot() . $uri)) { return false; }
        
        $path = realpath(binarypool_config::getRoot() . $uri);
        if (is_dir($path)) {
            $path .= '/index.xml';
            $assetFile .= '/index.xml';
        }
        if (!is_file($path)) { return false; }
        
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
        $oldAsset = new binarypool_asset($path);
        $asset = new binarypool_asset($path);
        $asset->setExpiry(time() + ($ttl * 24 * 60 * 60));
        file_put_contents($path, $asset->getXML());
        
        // Update views
        binarypool_views::updated($bucket, $assetFile, $oldAsset);
        $this->setResponseCode(204);
        return true;
    }
}
