<?php
require_once(dirname(__FILE__).'/../../binarypool/config.php');
require_once(dirname(__FILE__).'/../../binarypool/asset.php');

class api_command_view extends api_command_base {
    protected function execute() {
        $uri = $this->request->getPath();
        $storage = new binarypool_storage($this->bucket);
        
        // Access control
        if (!$storage->fileExists($uri)) {
            throw new binarypool_exception(115, 404, "File not found: " . $uri);
        }
        if (!$storage->isDir($uri)) {
            throw new binarypool_exception(115, 404, "File not found: " . $uri);
        }
        
        // List all assets matching the view
        $xml = '<status method="view">';
        $xml .= '<bucket>' . htmlspecialchars($this->bucket) . '</bucket>';
        
        // Remove leading slash and bucket name
        $dir = substr($uri, 2 + strlen($this->bucket));
        $files = $storage->listDir($dir);
        foreach ($files as $file) {
            $asset = $storage->getAssetObject($file);
            $xml .= '<file id="' . htmlspecialchars($asset->getHash()) . '">';
            $xml .= htmlspecialchars($asset->getBasePath());
            $xml .= '</file>';
        }
        
        $xml .= '</status>';
        array_push($this->data, new api_model_xml($xml));
    }
}
