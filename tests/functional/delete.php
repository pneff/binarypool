<?php
require_once(dirname(__FILE__) . "/../base/functional.php");

/**
 * Tests the DELETE method of the Binary Pool.
 */
class test_func_delete extends test_base_functional {
    function testDeleteAsset() {
        $this->upload();
        $this->delete('/test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 204);
        $this->assertEqual('', $response->getContents());
    }
    
    /**
     * Check that the asset is actually deleted.
     */
    function testDeleteAssetCheckDeleted() {
        $this->testDeleteAsset();
        
        $this->get('/test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 404);
    }
}
