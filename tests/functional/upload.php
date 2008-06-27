<?php
require_once(dirname(__FILE__) . "/../base/functional.php");

/**
 * Tests if we can upload a file and get a correct
 * answer back.
 */
class test_func_upload extends test_base_functional {
    function testUpload() {
        $this->post('/test', array(
            'Type' => 'IMAGE',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/vw_golf.jpg'),
        ));
        
        $response = api_response::getInstance();
        $headers = $response->getHeaders();
        $this->assertEqual($response->getCode(), 201);
        $this->assertEqual('test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml', $headers['Location']);
        $this->assertEqual('test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml', $headers['X-Asset']);
        $this->assertText('/saved/asset', 'test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
    }
    
    /**
     * Tests error handling if we don't provide a file.
     */
    function testUploadWithoutFile() {
        $this->post('/test', array('Type' => 'IMAGE'));
        $this->assertEqual(api_response::getInstance()->getCode(), 400);
        $this->assertText('/error/code', '109');
        $this->assertText('/error/msg', 'No file uploaded.');
    }
    
    /**
     * Tests error handling if we don't provide an upload type.
     */
    function testUploadWithoutType() {
        $this->post('/test', array(
            'File' => '@'.realpath(dirname(__FILE__).'/../res/vw_golf.jpg'),
        ));
        $this->assertEqual(api_response::getInstance()->getCode(), 400);
        $this->assertText('/error/code', '110');
        $this->assertText('/error/msg', 'Type param not given.');
    }
    
    /**
     * Tests error handling if the upload type is wrong.
     */
    function testUploadWithInvalidType() {
        $this->post('/test', array(
            'Type' => 'img',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/vw_golf.jpg'),
        ));
        
        $this->assertEqual(api_response::getInstance()->getCode(), 400);
        $this->assertText('/error/code', '111');
        $this->assertText('/error/msg', 'Invalid upload type: img');
    }
    
    /**
     * Tests if the file name is kept the same on uploading.
     */
    function testUploadFilename() {
        $this->testUpload();
        
        // Get asset file
        $this->get('/test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $this->assertEqual(api_response::getInstance()->getCode(), 200);
        $res = api_helpers_xpath::getNodes($this->responseDom, '/registry/items/item[@isRendition="false"]/location');
        $this->assertEqual(count($res), 1);
        $this->assertEqual('vw_golf.jpg', basename($res[0]->nodeValue));
    }
    
    /**
     * Verifies that we get correct error messages back when
     * we upload to a non-existing bucket.
     */
    function testUploadInvalidBucket() {
        $this->post('/nosuchbucket', array(
            'Type' => 'IMAGE',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/vw_golf.jpg'),
        ));
        
        $this->assertEqual(api_response::getInstance()->getCode(), 404);
        $this->assertText('/error/code', '100');
        $this->assertText('/error/msg', 'Bucket not defined: nosuchbucket');
    }
    
    /**
     * Verifies that we get correct error messages back when
     * we upload without specifying a bucket in the path.
     */
    function testUploadWithoutBucket() {
        $this->post('/', array(
            'Type' => 'IMAGE',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/vw_golf.jpg'),
        ));
        
        $this->assertEqual(api_response::getInstance()->getCode(), 400);
        $this->assertText('/error/code', '101');
        $this->assertText('/error/msg', 'No bucket given in the path.');
    }
    
    /**
     * Tests if we can retrieve the uploaded asset file again via
     * HTTP GET.
     */
    function testGetUploadedAssetFile() {
        $this->testUpload();
        
        // Get asset file
        $this->get('/test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $this->assertEqual(api_response::getInstance()->getCode(), 200);
        $this->assertText('/registry/@version', '3.0');
        $res = api_helpers_xpath::getNodes($this->responseDom, '/registry/items/item');
        $this->assertEqual(count($res), 3);
    }
    
    /**
     * Tests if we can retrieve the uploaded asset file by just
     * accessing the directory.
     */
    function testGetUploadedAssetDirectory() {
        $this->testUpload();
        
        // Get asset file
        $this->get('/test/09/096dfa489bc3f21df56eded2143843f135ae967e');
        $this->assertEqual(api_response::getInstance()->getCode(), 200);
        $this->assertText('/registry/@version', '3.0');
        $res = api_helpers_xpath::getNodes($this->responseDom, '/registry/items/item');
        $this->assertEqual(count($res), 3);
    }
    
    /**
     * Tests if we can retrieve the uploaded image file again via
     * HTTP GET.
     */
    function testGetUploadedImageFile() {
        $this->testUpload();
        
        // Get asset file
        $this->get('/test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        
        // Get path to original file
        $uri = $this->getText('/registry/items/item[@isRendition="false"]/location');
        
        // Download original file
        $this->get('/' . $uri);
        $response = api_response::getInstance();
        $headers = $response->getHeaders();
        $this->assertEqual($response->getCode(), 200);
        $this->assertEqual('image/jpeg', $headers['Content-Type']);
        // TODO: The body should be tested as well. But because of sendfile
        //       that's not easily done.
    }
    
    /**
     * Upload a movie
     */
    function testUploadFlashMovie() {
        $this->post('/test', array(
            'Type' => 'MOVIE',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/swiss-kurier.swf'),
        ));
        $headers = api_response::getInstance()->getHeaders();
        $this->assertEqual(api_response::getInstance()->getCode(), 201);
        $this->assertEqual('test/55/55395db0c5867a542da27aed649e661e25430ffc/index.xml', $headers['Location']);
        $this->assertText('/saved/asset', 'test/55/55395db0c5867a542da27aed649e661e25430ffc/index.xml');
    }
    
    /**
     * Test if the asset file of an uploaded movie is correct.
     */
    function testUploadFlashMovieAssetFile() {
        $this->testUploadFlashMovie();
        
        // Get asset file
        $this->get('/test/55/55395db0c5867a542da27aed649e661e25430ffc/index.xml');
        $this->assertEqual(api_response::getInstance()->getCode(), 200);
        
        $this->assertNode('/registry/items/item[@isRendition="false"]/location');
        $uri = $this->getText('/registry/items/item[@isRendition="false"]/location');
        $this->assertEqual('swiss-kurier.swf', basename($uri));
        
        $this->assertNotNode('/registry/items/item[@isRendition="true"]/location');
        
        $this->assertText('/registry/items/item/@type', 'MOVIE');
        $this->assertText('/registry/items/item/webobject/objectWidth', '468');
        $this->assertText('/registry/items/item/webobject/objectHeight', '60');
        $this->assertText('/registry/items/item/mimetype', 'application/x-shockwave-flash');
        $this->assertText('/registry/items/item/size', '10442');
    }
    
    /**
     * Test if the callback is stored correctly for
     * doing deletions.
     */
    function testUploadWithCallback() {
        $this->post('/test', array(
            'Type' => 'IMAGE',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/vw_golf.jpg'),
            'Callback' => 'http://binarypool/falsetest'
        ));
        $this->assertEqual(api_response::getInstance()->getCode(), 201);
        $headers = api_response::getInstance()->getHeaders();
        $this->assertEqual('test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml', $headers['Location']);
        
        // Parse
        $this->assertText('/saved/asset', 'test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        
        // Get asset file
        $this->get('/test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $this->assertEqual(api_response::getInstance()->getCode(), 200);
        $this->assertText('/registry/callback', 'http://binarypool/falsetest');
    }
    
    /**
     * Test if the callback is stored correctly when modifying an
     * existing asset.
     */
    function testUploadWithAnotherCallback() {
        $this->testUploadWithCallback();
        
        $this->post('/test', array(
            'Type' => 'IMAGE',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/vw_golf.jpg'),
            'Callback' => 'http://binarypool/anothertest'
        ));
        $this->assertEqual(api_response::getInstance()->getCode(), 201);
        $headers = api_response::getInstance()->getHeaders();
        $this->assertEqual('test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml', $headers['Location']);
        
        // Get asset file
        $this->get('/test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $this->assertEqual(api_response::getInstance()->getCode(), 200);
        
        // Get callbacks
        $res = api_helpers_xpath::getNodes($this->responseDom, '/registry/callback');
        $this->assertEqual(count($res), 2, "Expected two callbacks to be in asset file - got " . count($res));
        $this->assertEqual($res[0]->nodeValue, 'http://binarypool/falsetest');
        $this->assertEqual($res[1]->nodeValue, 'http://binarypool/anothertest');
    }
    
    /**
     * Test if we can upload by giving an URL.
     */
    function testUploadByURL($url = 'http://staticlocal.ch/images/logo.gif') {
        $this->post('/test', array(
            'Type' => 'IMAGE',
            'URL' => $url,
        ));
        
        $this->assertEqual(api_response::getInstance()->getCode(), 201);
        $headers = api_response::getInstance()->getHeaders();
        $this->assertEqual('test/d5/d5637d2355ab3e56b875bc32f2ffff45dcca781b/index.xml', $headers['Location']);
        $this->assertText('/saved/asset', 'test/d5/d5637d2355ab3e56b875bc32f2ffff45dcca781b/index.xml');
    }
    
    /**
     * Tests if the file name is kept the same on uploading.
     */
    function testUploadFilenameByURL() {
        $this->testUploadByURL();
        
        // Get asset file
        $this->get('/test/d5/d5637d2355ab3e56b875bc32f2ffff45dcca781b/index.xml');
        $this->assertEqual(api_response::getInstance()->getCode(), 200);
        
        $this->assertNode('/registry/items/item[@isRendition="false"]/location');
        $location = $this->getText('/registry/items/item[@isRendition="false"]/location');
        $this->assertEqual('logo.gif', basename($location));
    }
    
    /**
     * Test when the URL doesn't exist
     */
    function testUploadImageNotFound() {
        $url = 'http://staticlocal.ch/images/bad_notfound_XXX1234.gif';
        $urlhash = 'ae9d5e2a433f2859d75b9b29c077dae42577b08e';
        $view = "/test/downloaded/ae/ae9d5e2a433f2859d75b9b29c077dae42577b08e";
        
        $this->post('/test', array(
            'Type' => 'IMAGE',
            'URL' => $url,
        ));
        
        $this->assertEqual(api_response::getInstance()->getCode(), 400);
        
        // Get asset file
        $this->get($view);
        $this->assertEqual(api_response::getInstance()->getCode(), 404);

        $this->get($view.'/index.xml');
        $this->assertEqual(api_response::getInstance()->getCode(), 404);
        
        $this->assertEqual(
                readlink(binarypool_config::getRoot() . $view),
                '/dev/null',
                "Symlink to /dev/null missing"
            );
    }
    
    /**
     * Tests that the query string is removed from the filename when uploading.
     */
    function testUploadRemoveQuerystring() {
        $this->post('/test', array(
            'Type' => 'IMAGE',
            'URL' =>  'http://staticlocal.ch/images/buttons/directories.png?v=1',
        ));

        $headers = api_response::getInstance()->getHeaders();
        $this->assertEqual(api_response::getInstance()->getCode(), 201);
        $this->assertEqual('test/64/64f8b04dc2f1f2347a72e2e959c10a1b1487ac45/index.xml', $headers['Location']);
        
        // Get asset file
        $this->get('/test/64/64f8b04dc2f1f2347a72e2e959c10a1b1487ac45/index.xml');
        $this->assertEqual(api_response::getInstance()->getCode(), 200);
        
        $this->assertNode('/registry/items/item[@isRendition="false"]/location');
        $location = $this->getText('/registry/items/item[@isRendition="false"]/location');
        $this->assertEqual('directories.png', basename($location));
    }
    
    /**
     * Test if we can upload XML files.
     */
    function testUploadXML() {
        $this->post('/test', array(
            'Type' => 'XML',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/xmlfile.xml'),
        ));
        
        $this->assertEqual(api_response::getInstance()->getCode(), 201);
        $headers = api_response::getInstance()->getHeaders();
        $this->assertEqual('test/5e/5eaf447e8850037e46e8d27eb23447834e9a4075/index.xml', $headers['Location']);
        $this->assertEqual('test/5e/5eaf447e8850037e46e8d27eb23447834e9a4075/index.xml', $headers['X-Asset']);
        
        // Parse
        $this->assertText('/saved/asset', 'test/5e/5eaf447e8850037e46e8d27eb23447834e9a4075/index.xml');
    }
    
    /**
     * Non-wellformed XML documents are rejected.
     */
    function testUploadMalformedXML() {
        $this->post('/test', array(
            'Type' => 'XML',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/xmlfile-invalid.xml'),
        ));
        $this->assertEqual(api_response::getInstance()->getCode(), 400);
        
        // Parse
        $this->assertText('/error/code', '118');
        $this->assertText('/error/msg', 'XML document is not valid.');
    }
    
    /**
     * Invalid XML documents are rejected.
     */
    function testUploadInvalidXML() {
        $this->post('/test', array(
            'Type' => 'XML',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/xmlfile-malformed.xml'),
        ));
        $this->assertEqual(api_response::getInstance()->getCode(), 400);
        
        // Parse
        $this->assertText('/error/code', '117');
        $this->assertText('/error/msg', 'XML document is not well-formed.');
    }
    
    /**
     * Test that uploading a file called "index.xml" is not allowed.
     */
    function testUploadIndexXml() {
        $this->post('/test', array(
            'Type' => 'XML',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/index.xml'),
        ));
        $this->assertEqual(api_response::getInstance()->getCode(), 201);
        $headers = api_response::getInstance()->getHeaders();
        $this->assertEqual('test/83/83496e09c32e967ddace8e82154db08c6c8700ad/index.xml', $headers['Location']);
        $this->assertEqual('test/83/83496e09c32e967ddace8e82154db08c6c8700ad/index.xml', $headers['X-Asset']);
        
        // Get asset file
        $this->get('/test/83/83496e09c32e967ddace8e82154db08c6c8700ad/index.xml');
        $this->assertEqual(api_response::getInstance()->getCode(), 200);
        
        $this->assertNode('/registry/items/item[@isRendition="false"]/location');
        $location = $this->getText('/registry/items/item[@isRendition="false"]/location');
        $this->assertEqual('index-document.xml', basename($location));
    }

    /**
     * Uploads a file and sets a specific rendition.
     */
    function testUploadRendition() {
        $this->post('/test', array(
            'Type' => 'IMAGE',
            'File' => '@'.realpath(dirname(__FILE__).'/../res/vw_golf_smaller.jpg'),
            'File_detailpage' => '@'.realpath(dirname(__FILE__).'/../res/vw_golf_blur.jpg'),
        ));
        $this->assertEqual(api_response::getInstance()->getCode(), 201);
        $headers = api_response::getInstance()->getHeaders();
        $this->assertEqual('test/cb/cbf9f9f453acaba556e00b48951815da5611f975/index.xml', $headers['Location']);
        $this->assertEqual('test/cb/cbf9f9f453acaba556e00b48951815da5611f975/index.xml', $headers['X-Asset']);
    }
    
    /**
     * Tests that the correct rendition is saved.
     */
    function testUploadRenditionVerify() {
        $this->testUploadRendition();
        
        $this->get('/test/cb/cbf9f9f453acaba556e00b48951815da5611f975/index.xml');
        $this->assertEqual(api_response::getInstance()->getCode(), 200);
        $localHash = sha1_file(dirname(__FILE__).'/../res/vw_golf_blur.jpg');
        $remoteFile = $this->getText('/registry/items/item[rendition="detailpage"]/location');
        $remoteHash = sha1_file(binarypool_config::getRoot() . '/' . $remoteFile);
        $this->assertEqual($localHash, $remoteHash);
    }
}
