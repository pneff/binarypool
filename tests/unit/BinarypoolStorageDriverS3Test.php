<?php
require_once("BinarypoolTestCase.php");
require_once('simpletest/mock_objects.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/config.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/storage_driver_s3.php');

Mock::generate('S3_Wrapper', 'Mock_S3_Wrapper');

/**
 * Tests the binarypool_storage_driver_s3 class.
 */
class BinarypoolStorageDriverS3Test extends BinarypoolTestCase {
    function testAbsolutize() {
        $bucket = $this->getS3Bucket();
        $storage = new binarypool_storage_driver_s3($bucket['storage']);
        $this->assertEqual(
            $storage->absolutize('myfile'),
            'http://bin.staticlocal.ch/myfile');
    }
    
    function testSave() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->setReturnValue('getObjectInfo', false);
        $s3->setReturnValue('putObjectFile', true);
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3);
        $this->assertEqual($storage->save($this->testfile, 'test/uploaded.jpg'), true);
    }
    
    function testSaveExpectS3Action() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('getObjectInfo', array(
            'bin.staticlocal.ch',
            'test/uploaded.jpg',
            false
        ));
        $s3->setReturnValue('getObjectInfo', false);
        $s3->expectOnce('putObjectFile', array(
            $this->testfile,
            'bin.staticlocal.ch',
            'test/uploaded.jpg',
            S3::ACL_PUBLIC_READ,
            array(),
            'image/jpeg'
        ));
        $s3->setReturnValue('putObjectFile', true);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3);
        $this->assertEqual($storage->save($this->testfile, 'test/uploaded.jpg'), true);
    }
    
    function testSaveExisting() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('getObjectInfo', array(
            'bin.staticlocal.ch',
            'test/uploaded.jpg',
            false
        ));
        $s3->setReturnValue('getObjectInfo', true);
        $s3->expectNever('putObjectFile');
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3);
        $this->assertEqual($storage->save($this->testfile, 'test/uploaded.jpg'), true);
    }

    function testGetRenditionDirectory() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3);
        
        $tmpdir = $storage->getRenditionsDirectory('files/abc/def');
        $expectedDir = realpath(sys_get_temp_dir() . '/binarypool_s3_tmp') . '/';
        $this->assertIdentical(strpos($tmpdir, $expectedDir), 0, "The generated tmpdir was not created inside the base temp path. - %s");
        $this->assertNotEqual($tmpdir, $expectedDir, "The generated tmpdir should be longer than just the base temp path. - %s");
        $this->assertTrue(file_exists($tmpdir));
        $this->assertTrue(is_dir($tmpdir));
    }
    
    function testSaveRenditions() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3);
        
        $renditions = array(
            '/somedir/test-file/abc.jpg',
            '/somedir/test-file/bar.jpg',
        );
        $retval = array(
            'mydir/abc.jpg',
            'mydir/bar.jpg',
        );
        
        $this->assertEqual(
            $storage->saveRenditions($renditions, 'mydir'),
            $retval);
    }

    function testSaveRenditionsAssertS3Interaction() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3);
        
        $file1 = dirname(__FILE__) . '/../res/vw_golf.jpg';
        $file2 = dirname(__FILE__) . '/../res/emil_frey_logo.pdf';
        $renditions = array($file1, $file2);
        
        $s3->expectAt(0, 'putObjectFile', array(
            $file1,
            'bin.staticlocal.ch',
            'mydir/vw_golf.jpg',
            S3::ACL_PUBLIC_READ,
            array(),
            'image/jpeg'
        ));
        $s3->expectAt(1, 'putObjectFile', array(
            $file2,
            'bin.staticlocal.ch',
            'mydir/emil_frey_logo.pdf',
            S3::ACL_PUBLIC_READ,
            array(),
            'application/pdf'
        ));
        $s3->setReturnValue('putObjectFile', true);
        $storage->saveRenditions($renditions, 'mydir');
    }

    function testRename() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3);
        $this->assertEqual($storage->rename('test/abc/def', 'test/Trash/def'), true);
    }

    function testRenameAssertS3Interaction() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3);
        $s3->expectOnce('getBucket', array('bin.staticlocal.ch', 'test/abc/def/'));
        $s3->setReturnValue('getBucket', array(
            'test/abc/def/test.gif' => array(
                'name' => 'test/abc/def/test.gif',
                'time' => strtotime('2008-06-26 18:53:20'),
                'size' => 30,
                'hash' => 'somehash',
            )
        ));
        $s3->expectOnce('copyObject', array(
            'bin.staticlocal.ch', 'test/abc/def/test.gif',
            'bin.staticlocal.ch', 'test/Trash/def/test.gif',
        ));
        $s3->expectOnce('deleteObject', array(
            'bin.staticlocal.ch', 'test/abc/def/test.gif',
        ));
        
        $this->assertEqual($storage->rename('test/abc/def', 'test/Trash/def'), true);
    }

    protected function getS3Bucket() {
        $buckets = binarypool_config::getBuckets();
        return $buckets['test_s3'];
    }
    
    protected function getS3Client() {
        $bucket = $this->getS3Bucket();
        $s3 = new Mock_S3_Wrapper();
        return $s3;
    }
}
