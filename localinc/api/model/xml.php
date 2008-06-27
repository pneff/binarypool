<?php
/**
 * XML model object. Represents an XML string and returns the XML DOM
 * for that.
 */
class api_model_xml extends api_model_dom {
    public function __construct($xml) {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        parent::__construct($dom);
    }
}
