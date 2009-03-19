<?php
require_once(dirname(__FILE__).'/../../binarypool/storage.php');

class api_command_bucket extends api_command_base {
    
    protected function execute() {
        
        // raises exception if bucket does not exist
        $storage = new binarypool_storage($this->bucket);
        
        $xml = "<status method='getbucket'>";
        $xml .= '<bucket id="' . htmlspecialchars($this->bucket) . '" />';
        $xml .= "</status>";
        array_push($this->data, new api_model_xml($xml));
        
    }
}