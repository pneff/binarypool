<?php
require_once(dirname(__FILE__) . "/../base/functional.php");

/**
 * Tests the HEAD method of the Binary Pool.
 */
class test_func_head extends test_base_functional {
    /**
     * HEAD on root URL.
     */
    function testHome() {
        $this->head('/');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 200);
        $this->assertEqual('', $response->getContents());
    }
    
    /**
     * HEAD on a non-existing file.
     */
    function testNonexistingFile() {
        $this->head('/test/fkdshfjksdhffsdkhj/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 404);
        $this->assertEqual('', $response->getContents());
    }
    
    /**
     * HEAD on an asset file.
     */
    function testAssetFile() {
        $this->upload();
        $this->head('/test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 200);
        $this->assertEqual('', $response->getContents());
    }
}
