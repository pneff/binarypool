<?php
require_once("BinarypoolTestCase.php");
require_once('simpletest/mock_objects.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/fileobject.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/httpclient.php');

Mock::generate('binarypool_httpclient', 'Mock_binarypool_httpclient');

class BinarypoolFileobjectTest extends BinarypoolTestCase {
    function setUp() {
        parent::setUp();
        
        // Remove bucket
        if (file_exists(sys_get_temp_dir() . '/binarypool-fileobject/')) {
            $this->deltree(sys_get_temp_dir() . '/binarypool-fileobject/');
        }
        
        $this->url = 'http://staticlocal.ch/images/logo.gif';
        $this->tmpfile = sys_get_temp_dir() . '/binarypool-fileobject/' .
            '2c/2c76cf00527d36da0e2bd72071dbd9d480d97993';
    }
    
    function testInitLocalfile() {
        $http_mock = $this->getHttpMock();
        $http_mock->expectNever('get');
        $http_mock->expectNever('download');
        
        $file = realpath(dirname(__FILE__) . '/../res/index.xml');
        $fproxy = new binarypool_fileobject($file, $http_mock);
        $this->assertEqual($file, $fproxy->file);
        $this->assertEqual($fproxy->isRemote(), false);
    }
    
    function testInitRemotefile() {
        $http_mock = $this->getHttpMock();
        $http_mock->expectOnce('download', array($this->url, $this->tmpfile));
        $http_mock->setReturnValue('download', array(
            'code' => 200,
            'body' => false,
        ));
        
        $fproxy = new binarypool_fileobject($this->url, $http_mock);
        file_put_contents($this->tmpfile, 'some body');
        $this->assertNotNull($fproxy->file);
        $this->assertNotEqual($this->url, $fproxy->file);
        $this->assertEqual($this->tmpfile, $fproxy->file);
        $this->assertEqual($fproxy->isRemote(), true);
    }

    function testRemotefileContents() {
        $http_mock = $this->getHttpMock();
        $http_mock->expectOnce('download', array($this->url, $this->tmpfile));
        $http_mock->setReturnValue('download', array(
            'code' => 200,
            'body' => false,
        ));
        
        $fproxy = new binarypool_fileobject($this->url, $http_mock);
        file_put_contents($this->tmpfile, 'some body');
        $this->assertEqual($this->tmpfile, $fproxy->file);
        $this->assertEqual(sha1_file($fproxy->file),
            '754e8afdb33e180fbb7311eba784c5416766aa1c');
    }
    
    function testForgetCache() {
        $this->testRemotefileContents();
        $this->assertEqual(file_exists($this->tmpfile), true);
        
        binarypool_fileobject::forgetCache('http://staticlocal.ch/images/logo.gif');
        $this->assertEqual(file_exists($this->tmpfile), false);
        $this->assertEqual(file_exists(dirname($this->tmpfile)), false);
    }
    
    function testCachedRemote() {
        mkdir(dirname($this->tmpfile), 0755, true);
        file_put_contents($this->tmpfile, "some other body");
        
        $http_mock = $this->getHttpMock();
        $http_mock->expectNever('get');
        $http_mock->expectNever('download');
        $fproxy = new binarypool_fileobject($this->url, $http_mock);
        $this->assertEqual(sha1_file($fproxy->file),
            'eaaedb186627810613f2a9d454cfd47ea1dbee55');
    }
    
    function testRemotefile404() {
        $http_mock = $this->getHttpMock();
        $http_mock->expectOnce('download', array($this->url, $this->tmpfile));
        $http_mock->setReturnValue('download', array(
            'code' => 404,
            'body' => false,
        ));
        
        $fproxy = new binarypool_fileobject($this->url, $http_mock);
        $this->assertNull($fproxy->file);
        $this->assertEqual($fproxy->isRemote(), true);
    }
    
    protected function getHttpMock() {
        $http = new Mock_binarypool_httpclient();
        return $http;
    }
}
