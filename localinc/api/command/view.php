<?php
require_once(dirname(__FILE__).'/../../binarypool/config.php');
require_once(dirname(__FILE__).'/../../binarypool/asset.php');

class api_command_view extends api_command_base {
    protected function execute() {
        $uri = $this->request->getPath();
        
        // Access control
        if (!file_exists(binarypool_config::getRoot() . $uri)) {
            throw new binarypool_exception(115, 404, "File not found: " . $uri);
        }
        $path = realpath(binarypool_config::getRoot() . $uri);
        if (! is_dir($path)) {
            throw new binarypool_exception(115, 404, "File not found: " . $uri);
        }
        if (strpos($path, binarypool_config::getRoot()) !== 0) {
            // Apache should protect us against this, but you never know.
            throw new binarypool_exception(108, 403, "Access forbidden: $uri");
        }
        
        // List all assets matching the view
        $xml = '<status method="view">';
        $xml .= '<bucket>' . htmlspecialchars($this->bucket) . '</bucket>';
        if ($dirhandle = opendir($path)) {
            while (($file = readdir($dirhandle)) !== false) {
                if ($file != '.' && $file != '..') {
                    $assetfile = $path . '/' . $file . '/index.xml';
                    if (file_exists($assetfile)) {
                        $asset = new binarypool_asset($assetfile);
                        $xml .= '<file id="' . htmlspecialchars($asset->getHash()) . '">';
                        $xml .= htmlspecialchars($asset->getBasePath());
                        $xml .= '</file>';
                    }
                }
            }
            closedir($dirhandle);
        }
        $xml .= '</status>';
        array_push($this->data, new api_model_xml($xml));
    }
}
