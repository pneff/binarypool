<?php
require_once("BinarypoolTestCase.php");
require_once('simpletest/mock_objects.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/config.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/storage_driver_file.php');

/**
 * Tests the binarypool_storage_driver_file class.
 */
class BinarypoolStorageDriverFileTest extends BinarypoolTestCase {
    function setUp() {
        parent::setUp();
        
        $this->targetfile = binarypool_config::getRoot() . 'test/uploaded.jpg';
        if (file_exists($this->targetfile)) {
            unlink($this->targetfile);
        }
    }
    
    function testAbsolutize() {
        $storage = new binarypool_storage_driver_file();
        $this->assertEqual(
            $storage->absolutize('myfile'),
            binarypool_config::getRoot() . 'myfile');
    }
    
    function testAbsolutizeWithRootConstructor() {
        $storage = new binarypool_storage_driver_file('/tmp/binarypool-test-driver/');
        $this->assertEqual(
            $storage->absolutize('myfile'),
            '/tmp/binarypool-test-driver/myfile');
    }
    
    function testAbsolutizeWithRootConstructorWithMissingSlash() {
        $storage = new binarypool_storage_driver_file('/tmp/binarypool-test-driver');
        $this->assertEqual(
            $storage->absolutize('myfile'),
            '/tmp/binarypool-test-driver/myfile');
    }
    
    function testSave() {
        $storage = new binarypool_storage_driver_file();
        $this->assertEqual($storage->save($this->testfile, 'test/uploaded.jpg'), true);
    }
    
    function testSaveExpectFileCopied() {
        $this->testSave();
        $this->assertEqual(file_exists($this->targetfile), true, "The file was not copied to the intended destination. - %s");
    }
    
    function testGetRenditionDirectory() {
        $storage = new binarypool_storage_driver_file();
        $this->assertEqual(
            $storage->getRenditionsDirectory('files/abc/def'),
            binarypool_config::getRoot() . 'files/abc/def/');
    }
    
    function testSaveRenditions() {
        $renditions = array(
            '/somedir/test-file/abc.jpg',
            '/somedir/test-file/bar.jpg',
        );
        $retval = array(
            'mydir/abc.jpg',
            'mydir/bar.jpg',
        );
        
        $storage = new binarypool_storage_driver_file();
        $this->assertEqual(
            $storage->saveRenditions($renditions, 'mydir'),
            $retval);
    }
}
