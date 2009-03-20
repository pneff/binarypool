<?php
require_once(dirname(__FILE__).'/../../binarypool/config.php');
require_once(dirname(__FILE__).'/../../binarypool/exception.php');
require_once(dirname(__FILE__).'/../../binarypool/render.php');

/**
 * Command used during the migration from local.ch's binarypool 1 to
 * binarypool 2.
 *
 * Reads from the file system (from legacy binarypool root) first and
 * then goes through to the new binarypool system.
 */
class api_command_migrationserve extends api_command_serve {
    public function __construct($route) {
        parent::__construct($route);
        
        $this->area = $route['area'];
        switch ($route['stage']) {
            case 'trunk':
                $this->stage = 'development';
                break;
            case 'preview':
                $this->stage = 'preview';
                break;
            default:
                $this->stage = 'productive';
                break;
        }
    }

    protected function execute() {
        $localPath = "/static/" . $this->stage . "/" . $this->area .
            "/" . $this->getUri();

        if ($this->storageHasFile()) {
            parent::execute();
        } else if (file_exists($localPath)) {
            $mime = binarypool_mime::getMimeType($localPath);
            $this->response->setHeader('Content-Type', $mime);
            $this->response->setContentLengthOutput(false);
            $this->response->send();
            readfile($localPath);
            $this->ignoreView = true;
        } else {
            parent::execute();
        }
    }
    
    protected function storageHasFile() {
        try {
            $storage = new binarypool_storage($this->bucket);
            $path = $this->getPath();
            return $storage->fileExists($path);
        } catch (Exception $e) {
            return false;
        }
    }

    protected function getUri() {
        return $this->bucket . '/' . $this->route['asset'];
    }
}
