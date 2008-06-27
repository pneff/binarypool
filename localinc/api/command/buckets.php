<?php
require_once(dirname(__FILE__).'/../../binarypool/storage.php');

class api_command_buckets extends api_command_base {
    protected function execute() {
        $xml = "<status method='get'>";
        $buckets = binarypool_config::getBuckets();
        foreach (array_keys($buckets) as $bucket) {
            $xml .= '<bucket id="' . htmlspecialchars($bucket) . '" />';
        }
        $xml .= "</status>";
        array_push($this->data, new api_model_xml($xml));
    }
}
