<?php
require_once(dirname(__FILE__).'/../../binarypool/storage.php');
require_once(dirname(__FILE__).'/../../binarypool/config.php');

class api_command_sha1 extends api_command_base {
    protected function execute() {
        $hash = $this->route['hash'];
        $uri = $this->request->getPath();
        
        $storage = new binarypool_storage($this->bucket);
        $asset = $storage->getAssetBySha1($hash);
        if (!$storage->fileExists($asset)) {
            throw new binarypool_exception(115, 404, "File does not exist: $uri");
        }
        
        $this->setResponseCode(302);
        $this->response->setHeader('X-Asset', $asset);
        $this->response->setHeader('Content-Type', 'text/xml');
        $this->response->setContentLengthOutput(false);
        $storage->sendFile($asset);
        $this->response->send();
        $this->ignoreView = true;
    }
}
