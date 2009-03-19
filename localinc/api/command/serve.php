<?php
require_once(dirname(__FILE__).'/../../binarypool/config.php');
require_once(dirname(__FILE__).'/../../binarypool/exception.php');
require_once(dirname(__FILE__).'/../../binarypool/render.php');

class api_command_serve extends api_command_base {
    protected function execute() {
        $storage = new binarypool_storage($this->bucket);
        $path = $this->getPath();
        
        if ( preg_match('#index\.xml$#', $path) && $this->clientxsl ) {
            
            ob_start();
            $storage->sendFile($path);
            $content = ob_get_contents();
            ob_end_clean();
            
            $pi = '<?xml version="1.0" encoding="UTF-8"?>';
            $pi .= '<?xml-stylesheet type="text/xsl" href="/static/xsl/index.xsl"?>';

            $content = preg_replace('#<\?xml version="1.0" encoding="UTF-8"\?>#', $pi, $content);
            $this->sendHeaders('text/xml; charset=utf-8');
            print $content;
            
        } else {
            $mime = binarypool_mime::getMimeType($storage->absolutize($path));
            $this->sendHeaders($mime);
            $storage->sendFile($path);
        }
        
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
    
    /**
     * Injects the processing instruction to include the client
     * side XSL in the output
     */
    protected function sendHeaders($mime) {
        $this->response->setHeader('Content-Type', $mime);
        // Turns off output buffering,
        // so big files are streamed
        $this->response->setContentLengthOutput(false);
        $this->response->send();
    }
}
