<?php
require_once(dirname(__FILE__) . "/../base/functional.php");

/**
 * Tests if the views are correctly maintained when using the API.
 */
class test_func_view extends test_base_functional {
    /**
     * Tests if we can retrieve the uploaded asset file again via
     * HTTP GET.
     */
    function testGetAssetByCreationDate() {
        $this->upload();
        $date = date('Y/m/d');
        
        $this->get('/test/created/' . $date . '/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 200);
        $this->assertText('/registry/@version', '3.0');
        $this->assertText('/registry/items/item[@isRendition="false"]/location', 'test/09/096dfa489bc3f21df56eded2143843f135ae967e/vw_golf.jpg');
    }
    
    /**
     * Tests if we can retrieve a list of all items by creation date.
     */
    function testGetListByCreationDate() {
        $this->upload();
        $date = date('Y/m/d');
        
        $this->get('/test/created/' . $date);
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 200);
        $this->assertText('/view/asset/id', '096dfa489bc3f21df56eded2143843f135ae967e');
        $this->assertText('/view/asset/path', 'test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
    }
    
    /**
     * Version 2 of the view output. ID and path will disappear over time.
     */
    function testGetListByCreationDateV2() {
        $this->upload();
        $date = date('Y/m/d');
        
        $this->get('/test/created/' . $date);
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 200);
        $this->assertText('/view/asset/asset', 'test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $this->assertAttribute('/view/asset/id@deprecated', 'true');
        $this->assertAttribute('/view/asset/path@deprecated', 'true');
    }
    
    /**
     * Tests if we get a correct error for a view which does not exist.
     */
    function testGetNonexistingView() {
        $this->get('/test/created/2005/11/03');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 404);
        $this->assertText('/error/code', '115');
    }
    
    /**
     * Tests if we can test for the existence of an uploaded asset file
     * again via HTTP HEAD.
     */
    function testHeadAssetByCreationDate() {
        $this->upload();
        $date = date('Y/m/d');
        $this->head('/test/created/' . $date . '/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 200);
        $this->assertEqual('', $response->getContents());
    }
    
    /**
     * Tests if we can retrieve a list of all items by creation date.
     */
    function testHeadListByCreationDate() {
        $this->upload();
        $date = date('Y/m/d');
        
        $this->head('/test/created/' . $date);
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 200);
        $this->assertEqual('', $response->getContents());
    }
    
    /**
     * Tests if we get a correct error for a view which does not exist.
     */
    function testHeadNonexistingView() {
        $this->head('/test/created/2005/11/03');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 404);
        $this->assertEqual('', $response->getContents());
    }

    /**
     * Tests if we can retrieve the uploaded asset file again via
     * it's hash.
     */
    function testGetAssetByHash() {
        $this->upload();
        $this->get('/test/sha1/096dfa489bc3f21df56eded2143843f135ae967e');
        $response = api_response::getInstance();
        $headers = $response->getHeaders();
        $this->assertEqual($response->getCode(), 302);
        
        $this->assertText('/registry/@version', '3.0');
        $this->assertText('/registry/items/item[@isRendition="false"]/location', 'test/09/096dfa489bc3f21df56eded2143843f135ae967e/vw_golf.jpg');
        $this->assertEqual('test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml', $headers['X-Asset']);
    }
    
    /**
     * Tests if the hash directory is not mapped so that only the sha1 itself works.
     */
    function testGetImageByHash() {
        $this->upload();
        $this->get('/test/sha1/096dfa489bc3f21df56eded2143843f135ae967e/vw_golf.jpg');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 404);
    }
    
    /**
     * Only access directly by hash should work - nothing else.
     */
    function testGetIndexFileByHash() {
        $this->upload();
        $this->get('/test/sha1/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 404);
    }
    
    /**
     * Tests if we get a correct error for a file which does not exist by hash.
     */
    function testGetNonexistingHash() {
        $this->get('/test/sha1/abcdefghijklmnopqrstuvwxyzabcdefghijklmn/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 404);
    }

    /**
     * Tests if we can test for the existence of an uploaded file
     * by it's hash.
     */
    function testHeadAssetByHash() {
        $this->upload();
        $this->head('/test/sha1/096dfa489bc3f21df56eded2143843f135ae967e');
        $response = api_response::getInstance();
        $headers = $response->getHeaders();
        $this->assertEqual($response->getCode(), 302);
        $this->assertEqual('test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml', $headers['X-Asset']);
        $this->assertEqual('', $response->getContents());
    }
    
    /**
     * Tests if we get a correct error for a hash which does not exist.
     */
    function testHeadNonexistingHash() {
        $this->head('/test/sha1/abcdefghijklmnopqrstuvwxyzabcdefghijklmn/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 404);
        $this->assertEqual('', $response->getContents());
    }
    
    /**
     * Tests if we get a correct error for a hash which does not exist.
     * This test goes to the directory instead of the asset file.
     */
    function testHeadNonexistingDirectoryHash() {
        $this->head('/test/sha1/abcdefghijklmnopqrstuvwxyzabcdefghijklmn');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 404);
        $this->assertEqual('', $response->getContents());
    }
}
