<?php
class api_command_delete extends api_command_base {
    protected function execute() {
        $uri = $this->request->getPath();
        
        // Deletions allowed on bucket?
        $buckets = binarypool_config::getBuckets();
        if (!isset($buckets[$this->bucket]['allowDeletions']) || $buckets[$this->bucket]['allowDeletions'] == false) {
            throw new binarypool_exception(108, 403, "Deletions not allowed on this bucket.");
        }
        
        $storage = new binarypool_storage($this->bucket);
        $storage->delete($uri);
        $this->setResponseCode(204);
        $this->response->send();
        $this->ignoreView = true;
    }
}
