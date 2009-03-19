<?php
class test_base_functional extends api_testing_case_functional {
    function setUp() {
        parent::setUp();
        
        // Remove bucket
        $bucket = binarypool_config::getRoot() . 'test/';
        if (file_exists($bucket)) {
            $this->deltree($bucket);
        }
        
        // Remove trash
        if (file_exists(binarypool_config::getRoot() . 'Trash/')) {
            $this->deltree(binarypool_config::getRoot() . 'Trash/');
        }
    }
    
    /**
     * Removes a directory recursively.
     */
    protected function deltree($path) {
        if (!is_link($path) && is_dir($path)) {
            $entries = scandir($path);
            foreach ($entries as $entry) {
                if ($entry != '.' && $entry != '..') {
                    $this->deltree($path.DIRECTORY_SEPARATOR.$entry);
                }
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }
    
    /**
     * Creates a new uploaded file.
     */
    public function upload() {
        $this->post('/test', array(
            'Type' => 'IMAGE',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/vw_golf.jpg'),
        ));
    }

    /**
     * Creates a new uploaded file from a URL.
     */
    public function uploadUrl() {
        $this->post('/test', array(
            'Type' => 'IMAGE',
            'URL' => 'http://staticlocal.ch/images/logo.gif',
        ));
    }
    
    /**
     * Override OKAPI implementation with one that raises exceptions
     * on empty XML
     */
    protected function loadResponse() {
        $response = api_response::getInstance();
        $resp = $response->getContents();
        
        if ( empty($resp) ) {
            throw new api_testing_exception("Empty response document");
        }
        
        $dom = new DOMDocument();
        if ( !$dom->loadXML($resp) ) {
            throw new api_testing_exception(
                sprintf("Unable to load XML: '%s'", $resp)
                );
        }
        
        $this->responseDom = $dom;
    }
}
