<?php
require_once(dirname(__FILE__).'/../../binarypool/config.php');
require_once(dirname(__FILE__).'/../../binarypool/exception.php');
require_once(dirname(__FILE__).'/../../binarypool/render.php');

class api_command_serve extends api_command_base {
    protected function execute() {
        $path = $this->getPath();
        $mime = binarypool_mime::getMimeType($path);
        $this->response->setHeader('Content-Type', $mime);
        readfile($path);
        $this->response->send();
        $this->ignoreView = true;
    }
    
    /**
     * Get the path from the URI and implement access control.
     */
    protected function getPath() {
        $uri = $this->request->getPath();
        
        // Access control
        if (!file_exists(binarypool_config::getRoot() . $uri)) {
            throw new binarypool_exception(115, 404, "File not found: " . $uri);
        }
        $path = realpath(binarypool_config::getRoot() . $uri);
        if (is_dir($path)) {
            $path .= '/index.xml';
        }
        if (!is_file($path)) {
            throw new binarypool_exception(115, 404, "File not found: " . $uri);
        }
        if (strpos($path, binarypool_config::getRoot()) !== 0) {
            // Apache should protect us against this, but you never know.
            throw new binarypool_exception(108, 403, "Access forbidden: $uri");
        }
        
        return $path;
    }
}
