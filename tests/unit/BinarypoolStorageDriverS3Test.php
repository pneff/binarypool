<?php
require_once("BinarypoolTestCase.php");
require_once('simpletest/mock_objects.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/config.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/storage_driver_s3.php');

Mock::generate('S3_Wrapper', 'Mock_S3_Wrapper');
Mock::generate('api_cache', 'Mock_api_cache');

/**
 * Tests the binarypool_storage_driver_s3 class.
 */
class BinarypoolStorageDriverS3Test extends BinarypoolTestCase {
    function setUp() {
        parent::setUp();
        $this->time = time();
        binarypool_storage_driver_s3::resetMemoryCaches();
    }
    
    function testDown() {
        binarypool_storage_driver_s3::resetMemoryCaches();
    }
    
    function testAbsolutize() {
        $bucket = $this->getS3Bucket();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], null,
            $this->getMockCache());
        $this->assertEqual(
            $storage->absolutize('myfile'),
            'http://bin.staticlocal.ch/myfile');
    }
    
    function testSave() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->setReturnValue('getObjectInfo', false);
        $s3->setReturnValue('putObjectFile', true);
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->save($this->testfile, 'test/uploaded.jpg'), true);
    }
    
    function testSaveExpectS3Action() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('putObjectFile', array(
            $this->testfile,
            'bin.staticlocal.ch',
            'test/uploaded.jpg',
            S3::ACL_PUBLIC_READ,
            array(),
            'image/jpeg'
        ));
        $s3->setReturnValue('putObjectFile', true);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->save($this->testfile, 'test/uploaded.jpg'), true);
    }
    
    /**
     * Make sure getObjectInfo is not called.
     * This used to be called because we only uploaded files when they
     * didn't exist yet. This has since changed to always upload.
     */
    function testSaveForced() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectNever('getObjectInfo');
        $s3->expectOnce('putObjectFile');
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $storage->save($this->testfile, 'test/uploaded.jpg');
    }

    function testGetRenditionDirectory() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        
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
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        
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
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        
        $file1 = dirname(__FILE__) . '/../res/vw_golf.jpg';
        $file2 = dirname(__FILE__) . '/../res/emil_frey_logo.pdf';
        $tmpdir = $storage->getRenditionsDirectory('mydir');
        copy($file1, $tmpdir . '/vw_golf.jpg');
        $file1 = $tmpdir . '/vw_golf.jpg';
        copy($file2, $tmpdir . '/emil_frey_logo.pdf');
        $file2 = $tmpdir . '/emil_frey_logo.pdf';
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
    
    function testSaveRenditionsRemoveTmpDir() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        
        $file1 = dirname(__FILE__) . '/../res/vw_golf.jpg';
        $file2 = dirname(__FILE__) . '/../res/emil_frey_logo.pdf';
        $tmpdir = $storage->getRenditionsDirectory('mydir');
        copy($file1, $tmpdir . '/vw_golf.jpg');
        $file1 = $tmpdir . '/vw_golf.jpg';
        copy($file2, $tmpdir . '/emil_frey_logo.pdf');
        $file2 = $tmpdir . '/emil_frey_logo.pdf';
        $renditions = array($file1, $file2);
        
        $storage->saveRenditions($renditions, 'mydir');
        
        $this->assertEqual(file_exists($file1), false);
        $this->assertEqual(file_exists($file2), false);
        $this->assertEqual(file_exists($tmpdir), false);
    }

    function testRename() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->rename('test/abc/def', 'test/Trash/def'), true);
    }

    function testRenameAssertS3Interaction() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
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
    
    function testFileExistsFile() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('getObjectInfo', array('bin.staticlocal.ch', 'test/abc/def/index.xml', false));
        $s3->setReturnValue('getObjectInfo', true);
        $s3->expectNever('getBucket');
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->fileExists('test/abc/def/index.xml'), true);
    }
    
    function testFileExistsLeadingSlash() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('getObjectInfo', array('bin.staticlocal.ch', 'test/abc/def/index.xml', false));
        $s3->setReturnValue('getObjectInfo', true);
        $s3->expectNever('getBucket');
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->fileExists('/test/abc/def/index.xml'), true);
    }
    
    function testFileExistsDir() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('getObjectInfo', array('bin.staticlocal.ch', 'test/abc/def/', false));
        $s3->setReturnValue('getObjectInfo', false);
        $s3->expectOnce('getBucket', array('bin.staticlocal.ch', 'test/abc/def/'));
        $s3->setReturnValue('getBucket', array(
            'test/abc/def/test.gif' => array(
                'name' => 'test/abc/def/test.gif',
                'time' => strtotime('2008-06-26 18:53:20'),
                'size' => 30,
                'hash' => 'somehash',
            )
        ));
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->fileExists('test/abc/def/'), true);
    }
    
    function testFileExistsDirNone() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('getObjectInfo', array('bin.staticlocal.ch', 'test/abc/def', false));
        $s3->setReturnValue('getObjectInfo', false);
        $s3->expectOnce('getBucket', array('bin.staticlocal.ch', 'test/abc/def/'));
        $s3->setReturnValue('getBucket', array());
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->fileExists('test/abc/def'), false);
    }
    
    function testFileExistsDirGetBucketReturnsNull() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('getObjectInfo', array('bin.staticlocal.ch', 'test/abc/def', false));
        $s3->setReturnValue('getObjectInfo', false);
        $s3->expectOnce('getBucket', array('bin.staticlocal.ch', 'test/abc/def/'));
        $s3->setReturnValue('getBucket', null);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->fileExists('test/abc/def'), false);
    }
    
    function testIsFile() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('getObjectInfo', array('bin.staticlocal.ch', 'test/abc/def/index.xml', false));
        $s3->setReturnValue('getObjectInfo', true);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->isFile('test/abc/def/index.xml'), true);
    }
    
    function testIsFileLeadingSlash() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('getObjectInfo', array('bin.staticlocal.ch', 'test/abc/def/index.xml', false));
        $s3->setReturnValue('getObjectInfo', true);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->isFile('/test/abc/def/index.xml'), true);
    }
    
    function testIsFileNo() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('getObjectInfo', array('bin.staticlocal.ch', 'anything/else/as/file', false));
        $s3->setReturnValue('getObjectInfo', false);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->isFile('anything/else/as/file'), false);
    }
    
    function testIsDir() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectNever('getObjectInfo');
        $s3->expectOnce('getBucket', array('bin.staticlocal.ch', 'foo/bar/dir/'));
        $s3->setReturnValue('getBucket', array(
            'foo/bar/dir/test' => array(
                'name' => 'foo/bar/dir/test',
                'time' => strtotime('2008-06-26 18:53:20'),
                'size' => 30,
                'hash' => 'somehash',
            )
        ));
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->isDir('foo/bar/dir'), true);
    }
    
    function testIsDirLeadingSlash() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectNever('getObjectInfo');
        $s3->expectOnce('getBucket', array('bin.staticlocal.ch', 'foo/bar/dir/'));
        $s3->setReturnValue('getBucket', array());
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->isDir('/foo/bar/dir/'), false);
    }
    
    function testIsDirWithSlash() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectNever('getObjectInfo');
        $s3->expectOnce('getBucket', array('bin.staticlocal.ch', 'foo/bar/dir/'));
        $s3->setReturnValue('getBucket', array(
            'foo/bar/dir/test' => array(
                'name' => 'foo/bar/dir/test',
                'time' => strtotime('2008-06-26 18:53:20'),
                'size' => 30,
                'hash' => 'somehash',
            )
        ));
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->isDir('foo/bar/dir/'), true);
    }
    
    function testIsDirNo() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectNever('getObjectInfo');
        $s3->expectOnce('getBucket', array('bin.staticlocal.ch', 'foo/bar/dir/'));
        $s3->setReturnValue('getBucket', null);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->isDir('foo/bar/dir/'), false);
    }
    
    function testGetFile() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        
        $s3_response = new STDClass;
        $s3_response->error = false;
        $s3_response->code = 200;
        $s3_response->body = 'response data';
        $s3_response->headers = array();
        
        $s3->expectOnce('getObject', array('bin.staticlocal.ch', 'foo/bar/dir/index.xml'));
        $s3->setReturnValue('getObject', $s3_response);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->getFile('foo/bar/dir/index.xml'), 'response data');
    }
    
    function testGetFileError() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        
        $s3_response = new STDClass;
        $s3_response->error = false;
        $s3_response->code = 404;
        $s3_response->body = 'Some data';
        $s3_response->headers = array();
        
        $s3->expectOnce('getObject', array('bin.staticlocal.ch', 'foo/bar/dir/index.xml'));
        $s3->setReturnValue('getObject', $s3_response);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->getFile('foo/bar/dir/index.xml'), null);
    }
    
    function testIsAbsoluteStorage() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->isAbsoluteStorage(), true);
    }
    
    function testListDir() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('getBucket', array('bin.staticlocal.ch', 'foo/bar/dir/'));
        $s3->setReturnValue('getBucket', array(
            'foo/bar/dir/bxabcdef.link' => array(
                'name' => 'foo/bar/dir/bxabcdef.link',
                'time' => strtotime('2008-06-26 18:53:20'),
                'size' => 30,
                'hash' => 'somehash',
            )
        ));
        // Retrieve the link returned by getBucket
        $s3->expectAt(0, 'getObject', array('bin.staticlocal.ch', 'foo/bar/dir/bxabcdef.link'));
        $s3->setReturnValueAt(0, 'getObject', $this->prepareGetObjectResponse(
            json_encode(array('link' => '../../bx/bxabcdef/index.xml',
                        'mtime' => $this->time))));
        // Follow the link and retrieve the actual content
        $s3->expectAt(1, 'getObject', array('bin.staticlocal.ch', 'foo/bx/bxabcdef/index.xml'));
        $s3->setReturnValueAt(1, 'getObject', $this->prepareGetObjectResponse(
            file_get_contents(dirname(__FILE__).'/../res/example_asset.xml')));
        $s3->expectOnce('getObjectInfo');
        $s3->setReturnValue('getObjectInfo', true);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache(), $this->time);
        $retval = $storage->listDir('foo/bar/dir/');
        $this->assertEqual($retval, array(
            'market/f3/f3584d8f3729f7a91133648483068e73f27447b9/index.xml'
        ));
    }
    
    function testUnlink() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('deleteObject', array('bin.staticlocal.ch', 'myfile/is/here/index.xml'));
        $s3->setReturnValue('deleteObject', true);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->unlink('myfile/is/here/index.xml'), true);
    }
    
    function testUnlinkFalse() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $s3->expectOnce('deleteObject', array('bin.staticlocal.ch', 'myfile/index.xml'));
        $s3->setReturnValue('deleteObject', false);
        
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        $this->assertEqual($storage->unlink('myfile/index.xml'), false);
    }
    
    function testSymlink() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache(), $this->time);
        
        $s3->setReturnValue('getObjectInfo', false);
        $s3->expectOnce('putObject', array(
            json_encode(array('link' => '../../hashing/index.xml', 'mtime' => $this->time)),
            'bin.staticlocal.ch',
            'test/downloaded/otherhash.link',
            S3::ACL_PUBLIC_READ,
            array(),
            'application/x-symlink'
        ));
        $s3->setReturnValue('putObject', true);
        $storage->symlink('../../hashing/index.xml', 'test/downloaded/otherhash');
    }
    
    function testGetUrlLastModifiedNewFile() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache());
        
        $s3->expectOnce('getObjectInfo',
            array('bin.staticlocal.ch',
                  'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de.link',
                  false));
        $s3->setReturnValue('getObjectInfo', false);
        
        $retval = $storage->getURLLastModified(
            'http://www.patrice.ch/',
            'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de',
            'test_s3');
        $this->assertEqual($retval, array(
            'time' => 0, 'revalidate' => true, 'cache_age' => 0));
    }
    
    function testGetUrlFailedRefetch() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache(), $this->time);
        
        $s3->expectOnce('getObjectInfo',
            array('bin.staticlocal.ch',
                  'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de.link',
                  false));
        $s3->setReturnValue('getObjectInfo', true);
        $s3->expectAt(0, 'getObject', array('bin.staticlocal.ch',
            'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de.link'));
        $s3->setReturnValueAt(0, 'getObject', $this->prepareGetObjectResponse(
            json_encode(array('link' => '/dev/null', 'mtime' => $this->time - 9120))));
        $s3->expectOnce('deleteObject', array('bin.staticlocal.ch',
            'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de.link'));
        
        $retval = $storage->getURLLastModified(
            'http://www.patrice.ch/',
            'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de',
            'test_s3');
        $this->assertEqual($retval, array(
            'time' => 0, 'revalidate' => true, 'cache_age' => 9120));
    }
    
    function testGetUrlFailedNoRefetchYet() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache(), $this->time);
        
        $s3->expectOnce('getObjectInfo',
            array('bin.staticlocal.ch',
                  'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de.link',
                  false));
        $s3->setReturnValue('getObjectInfo', true);
        $s3->expectAt(0, 'getObject', array('bin.staticlocal.ch',
            'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de.link'));
        $s3->setReturnValueAt(0, 'getObject', $this->prepareGetObjectResponse(
            json_encode(array('link' => '/dev/null', 'mtime' => $this->time - 10))));
        $s3->expectNever('deleteObject');
        
        $interval = binarypool_config::getBadUrlExpiry() - 10;
        $exception = new binarypool_exception(122, 400,
            "File download failed 10 seconds ago. Re-fetching allowed in next time in $interval seconds: http://www.patrice.ch/");
        $this->expectException($exception);
        $storage->getURLLastModified(
            'http://www.patrice.ch/',
            'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de',
            'test_s3');
    }
    
    function testGetUrlFetchedRefetch() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache(), $this->time);
        
        $s3->expectOnce('getObjectInfo',
            array('bin.staticlocal.ch',
                  'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de.link',
                  false));
        $s3->setReturnValue('getObjectInfo', true);
        $s3->expectAt(0, 'getObject', array('bin.staticlocal.ch',
            'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de.link'));
        $s3->setReturnValueAt(0, 'getObject', $this->prepareGetObjectResponse(
            json_encode(array('link' => '../../../9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de/index.xml',
                              'mtime' => $this->time - 9129381))));
        
        $retval = $storage->getURLLastModified(
            'http://www.patrice.ch/',
            'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de',
        	'test_s3');
        $this->assertEqual($retval, array(
            'time' => $this->time - 9129381, 'revalidate' => true, 'cache_age' => 9129381));
    }
    
    function testGetUrlFetchedNoRefetchYet() {
        $bucket = $this->getS3Bucket();
        $s3 = $this->getS3Client();
        $storage = new binarypool_storage_driver_s3($bucket['storage'], $s3,
            $this->getMockCache(), $this->time);
        
        $s3->expectOnce('getObjectInfo',
            array('bin.staticlocal.ch',
                  'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de.link',
                  false));
        $s3->setReturnValue('getObjectInfo', true);
        $s3->expectAt(0, 'getObject', array('bin.staticlocal.ch',
            'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de.link'));
        $s3->setReturnValueAt(0, 'getObject', $this->prepareGetObjectResponse(
            json_encode(array('link' => '../../../9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de/index.xml',
                              'mtime' => $this->time - 10))));
        
        $retval = $storage->getURLLastModified(
            'http://www.patrice.ch/',
            'test/downloaded/9f/9fae60fc483eef3a55cbad16b9f13c94eb81a5de',
        	'test_s3');
        $this->assertEqual($retval, array(
            'time' => $this->time - 10, 'revalidate' => false, 'cache_age' => 10));
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
    
    protected function getMockCache() {
        return new Mock_api_cache();
    }
    
    protected function prepareGetObjectResponse($body) {
        $s3_response = new STDClass;
        $s3_response->error = false;
        $s3_response->code = 200;
        $s3_response->body = $body;
        $s3_response->headers = array();
        return $s3_response;
    }
}
