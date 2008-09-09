<?php
require_once(dirname(__FILE__).'/../../binarypool/config.php');
require_once(dirname(__FILE__).'/../../binarypool/exception.php');
require_once(dirname(__FILE__).'/../../binarypool/render.php');

class api_command_serve extends api_command_base {
    protected function execute() {
        $storage = new binarypool_storage($this->bucket);
        $path = $this->getPath();
        $mime = binarypool_mime::getMimeType($storage->absolutize($path));
        $this->response->setHeader('Content-Type', $mime);
        
        // Turn off output buffering, so big files don't cause problems
        $this->response->setContentLengthOutput(false);
        $this->response->send();
        $storage->sendFile($path);
        $this->ignoreView = true;
    }
    
    /**
     * Get the path from the URI and implement access control.
     */
    protected function getPath() {
        $uri = $this->getUri();
        $storage = new binarypool_storage($this->bucket);
        
        // Access control
        if (!$storage->fileExists($uri)) {
            throw new binarypool_exception(115, 404, "File not found: " . $uri);
        }
        if ($storage->isDir($uri)) {
            $uri .= '/index.xml';
        }
        if (!$storage->isFile($uri)) {
            throw new binarypool_exception(115, 404, "File not found: " . $uri);
        }
        
        return ltrim($uri, '/');
    }
    
    protected function getUri() {
        return $this->request->getPath();
    }
}
