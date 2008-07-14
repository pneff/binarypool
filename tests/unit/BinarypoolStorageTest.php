<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/config.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/storage.php');

/**
 * Tests the binarypool_storage class.
 */
class BinarypoolStorageTest extends BinarypoolTestCase {
    function setUp() {
        parent::setUp();
        $this->storage = new binarypool_storage('test');
    }
    
    function testConstruct() {
        $storage = new binarypool_storage('test');
        $this->assertIsA($storage, 'binarypool_storage');
    }

    function testConstructException() {
        $this->expectException(new binarypool_exception(100, 404, "Bucket not defined: no_such_bucket"));
        $storage = new binarypool_storage('no_such_bucket');
    }

    function testSave() {
        $this->assertFalse(file_exists(self::$BUCKET . '09/096dfa489bc3f21df56eded2143843f135ae967e/vw_golf.jpg'));

        $asset = $this->storage->save('IMAGE', array('_' => array('file' => $this->testfile)));
        $this->assertNotNull($asset);
        $this->assertEqual('test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml', $asset);
        $this->assertTrue(file_exists(self::$BUCKET . '09/096dfa489bc3f21df56eded2143843f135ae967e/vw_golf.jpg'),
            'Original file was not written to file system.');
        $this->assertTrue(file_exists(binarypool_config::getRoot() . $asset),
            'Asset file was not written to file system.');
    }

    /**
     * Tests that the renditions get generated and saved.
     */
    function testSaveRenditions() {
        $this->assertFalse(file_exists(self::$BUCKET . '09/096dfa489bc3f21df56eded2143843f135ae967e/vw_golf.jpg'));
        $this->assertFalse(file_exists(self::$BUCKET . '09/096dfa489bc3f21df56eded2143843f135ae967e/resultlist.jpg'));
        $this->assertFalse(file_exists(self::$BUCKET . '09/096dfa489bc3f21df56eded2143843f135ae967e/detailpage.jpg'));

        $asset = $this->storage->save('IMAGE', array('_' => array('file' => $this->testfile)));
        $this->assertTrue(file_exists(self::$BUCKET . '09/096dfa489bc3f21df56eded2143843f135ae967e/vw_golf.jpg'),
            'Original file was not written to file system.');
        $this->assertTrue(file_exists(self::$BUCKET . '09/096dfa489bc3f21df56eded2143843f135ae967e/resultlist.jpg'),
            '"resultlist" rendition was not written to file system.');
        $this->assertTrue(file_exists(self::$BUCKET . '09/096dfa489bc3f21df56eded2143843f135ae967e/detailpage.jpg'),
            '"detailpage" rendition was not written to file system.');
        $this->assertTrue(file_exists(binarypool_config::getRoot() . $asset),
            'Asset file was not written to file system.');
    }
    
    /**
     * Tests that a rendition can be forced in.
     */
    function testSaveAddRendition() {
        $this->assertFalse(file_exists(self::$BUCKET . 'cb/cbf9f9f453acaba556e00b48951815da5611f975/vw_golf_smaller.jpg'));
        
        $asset = $this->storage->save('IMAGE',
            array('_'          => array('file' => dirname(__FILE__).'/../res/vw_golf_smaller.jpg'),
                  'detailpage' => array('file' => dirname(__FILE__).'/../res/vw_golf_blur.jpg')));
        $this->assertNotNull($asset);
        $this->assertEqual('test/cb/cbf9f9f453acaba556e00b48951815da5611f975/index.xml', $asset);
        $this->assertTrue(file_exists(self::$BUCKET . 'cb/cbf9f9f453acaba556e00b48951815da5611f975/vw_golf_smaller.jpg'),
            'Original file was not written to file system.');
        $this->assertTrue(file_exists(self::$BUCKET . 'cb/cbf9f9f453acaba556e00b48951815da5611f975/vw_golf_blur.jpg'),
            'detailpage rendition file was not written to file system.');
        $this->assertTrue(file_exists(binarypool_config::getRoot() . $asset),
            'Asset file was not written to file system.');
    }
    
    function testSaveAddRenditionAssetFile() {
        $this->testSaveAddRendition();
        
        $dom = DOMDocument::load(self::$BUCKET . 'cb/cbf9f9f453acaba556e00b48951815da5611f975/index.xml');
        $xp = new DOMXPath($dom);
        
        $this->assertXPath($xp, '/registry/@version', '3.0');
        $this->assertXPath($xp, '/registry/items/item[3]/@type', 'IMAGE');
        $this->assertXPath($xp, '/registry/items/item[3]/@isRendition', 'true');
        $this->assertXPath($xp, '/registry/items/item[3]/rendition', 'detailpage');
        $this->assertXPath($xp, '/registry/items/item[3]/location', 'test/cb/cbf9f9f453acaba556e00b48951815da5611f975/vw_golf_blur.jpg');
    }
    
    function testSaveAddRenditionHash() {
        $this->testSaveAddRendition();
        $this->assertEqual('b0d08685a08e692de2c290c121c15ca336e36542',
                           sha1_file(self::$BUCKET . 'cb/cbf9f9f453acaba556e00b48951815da5611f975/vw_golf_blur.jpg'));
    }
    
    function testSavedAssetFile() {
        $asset = $this->storage->save('IMAGE', array('_' => array('file' => $this->testfile)));

        // Load asset file
        $dom = DOMDocument::load(binarypool_config::getRoot() . $asset);
        $xp = new DOMXPath($dom);
        
        // Test
        $this->assertXPath($xp, '/registry/@version', '3.0');
        $this->assertXPath($xp, '/registry/items/item[1]/@type', 'IMAGE');
        $this->assertXPath($xp, '/registry/items/item[1]/@isRendition', 'false');
        $this->assertXPath($xp, '/registry/items/item[1]/webobject/objectWidth', '557');
        $this->assertXPath($xp, '/registry/items/item[1]/webobject/objectHeight', '344');
        $this->assertXPath($xp, '/registry/items/item[1]/imageinfo/@isLandscape', 'true');
        $this->assertXPath($xp, '/registry/items/item[1]/imageinfo/width', '557');
        $this->assertXPath($xp, '/registry/items/item[1]/imageinfo/height', '344');
        $this->assertXPath($xp, '/registry/items/item[1]/location', 'test/09/096dfa489bc3f21df56eded2143843f135ae967e/vw_golf.jpg');
        $this->assertXPath($xp, '/registry/items/item[1]/mimetype', 'image/jpeg');
        $this->assertXPath($xp, '/registry/items/item[1]/size', '106237');

        // Test attributes
        $created = intval($this->getXPathValue($xp, '/registry/created'));
        $this->assertWithinMargin(time(), $created, 10);
        $expiry = intval($this->getXPathValue($xp, '/registry/expiry'));
        $this->assertWithinMargin(strtotime('+7 days'), $expiry, 10);
    }
    
    /**
     * Tests that the asset file contains the saved renditions.
     */
    function testSavedAssetFileWithRenditions() {
        $asset = $this->storage->save('IMAGE', array('_' => array('file' => $this->testfile)));

        // Load asset file
        $dom = DOMDocument::load(binarypool_config::getRoot() . $asset);
        $xp = new DOMXPath($dom);

        $res = $xp->query('/registry/items/item');
        $this->assertEqual($res->length, 3);
        
        // Rendition 1
        $this->assertXPath($xp, '/registry/items/item[rendition="resultlist"]/@type', 'IMAGE');
        $this->assertXPath($xp, '/registry/items/item[rendition="resultlist"]/@isRendition', 'true');
        $this->assertXPath($xp, '/registry/items/item[rendition="resultlist"]/webobject/objectWidth', '70');
        $this->assertXPath($xp, '/registry/items/item[rendition="resultlist"]/webobject/objectHeight', '43');
        $this->assertXPath($xp, '/registry/items/item[rendition="resultlist"]/imageinfo/@isLandscape', 'true');
        $this->assertXPath($xp, '/registry/items/item[rendition="resultlist"]/imageinfo/width', '70');
        $this->assertXPath($xp, '/registry/items/item[rendition="resultlist"]/imageinfo/height', '43');
        $this->assertXPath($xp, '/registry/items/item[rendition="resultlist"]/location', 'test/09/096dfa489bc3f21df56eded2143843f135ae967e/resultlist.jpg');
        $this->assertXPath($xp, '/registry/items/item[rendition="resultlist"]/mimetype', 'image/jpeg');
        
        // Rendition 2
        $this->assertXPath($xp, '/registry/items/item[rendition="detailpage"]/@type', 'IMAGE');
        $this->assertXPath($xp, '/registry/items/item[rendition="detailpage"]/@isRendition', 'true');
        $this->assertXPath($xp, '/registry/items/item[rendition="detailpage"]/webobject/objectWidth', '108');
        $this->assertXPath($xp, '/registry/items/item[rendition="detailpage"]/webobject/objectHeight', '67');
        $this->assertXPath($xp, '/registry/items/item[rendition="detailpage"]/imageinfo/@isLandscape', 'true');
        $this->assertXPath($xp, '/registry/items/item[rendition="detailpage"]/imageinfo/width', '108');
        $this->assertXPath($xp, '/registry/items/item[rendition="detailpage"]/imageinfo/height', '67');
        $this->assertXPath($xp, '/registry/items/item[rendition="detailpage"]/location', 'test/09/096dfa489bc3f21df56eded2143843f135ae967e/detailpage.jpg');
        $this->assertXPath($xp, '/registry/items/item[rendition="detailpage"]/mimetype', 'image/jpeg');
    }

    function testSaveInvalidFile() {
        $this->expectException(new binarypool_exception(103, 404, "File to save in binary pool does not exist: test.jpg"));
        $asset = $this->storage->save('IMAGE', array('_' => array('file' => 'test.jpg')));
        $this->assertNull($asset);
    }
    
    function testGetBySha1() {
        $asset = $this->storage->save('IMAGE', array('_' => array('file' => $this->testfile)));
        $this->assertEqual($asset, $this->storage->getAssetBySha1('096dfa489bc3f21df56eded2143843f135ae967e'));
    }

    /**
     * Delete an asset from the file system (moves it to the Trash).
     */
    function testDelete() {
        $date = date('Y/m/d');
        $trashDir = binarypool_config::getRoot() . 'Trash/' . $date . '/test/09/096dfa489bc3f21df56eded2143843f135ae967e';
        
        $asset = $this->storage->save('IMAGE', array('_' => array('file' => $this->testfile)));
        $this->assertTrue(is_dir(self::$BUCKET . '09/096dfa489bc3f21df56eded2143843f135ae967e'),
            'Asset directory was not created.');
        $this->assertFalse(file_exists($trashDir),
            'Trashed file already exists.');
        
        $this->storage->delete($asset);
        $this->assertFalse(is_dir(self::$BUCKET . '09/096dfa489bc3f21df56eded2143843f135ae967e'),
            'Asset directory was not deleted.');
        $this->assertTrue(is_dir(self::$BUCKET), 'Bucket directory was also deleted!');
        $this->assertTrue(file_exists($trashDir), 'Folder was not moved to trash.');
        $this->assertTrue(file_exists($trashDir . '/index.xml'), 'Asset file was not moved to trash.');
    }

    /**
     * Fix the extension according to MIME type.
     */
    function testSaveFixExtension() {
        $this->assertFalse(file_exists(self::$BUCKET . 'f0/f0fb27ea804fcdc0d4628071a45562aa2803267e/upload1.jpg'));

        $file = realpath(dirname(__FILE__).'/../res/upload1.bin');
        $asset = $this->storage->save('IMAGE', array('_' => array('file' => $file)));
        $this->assertNotNull($asset);
        $this->assertEqual('test/f0/f0fb27ea804fcdc0d4628071a45562aa2803267e/index.xml', $asset);
        $this->assertTrue(file_exists(self::$BUCKET . 'f0/f0fb27ea804fcdc0d4628071a45562aa2803267e/upload1.jpg'),
            'Original file was not written to file system.');
        $this->assertFalse(file_exists(self::$BUCKET . 'f0/f0fb27ea804fcdc0d4628071a45562aa2803267e/upload1.bin'),
            'Original file got written with wrong extension.');
        $this->assertTrue(file_exists(binarypool_config::getRoot() . $asset),
            'Asset file was not written to file system.');
    }

    /**
     * Fix the extension of manually uploaded renditions.
     */
    function testSaveRenditionFixExtension() {
        $this->assertFalse(file_exists(self::$BUCKET . 'cb/cbf9f9f453acaba556e00b48951815da5611f975/vw_golf_smaller.jpg'));

        $asset = $this->storage->save('IMAGE',
            array('_'          => array('file' => dirname(__FILE__).'/../res/vw_golf_smaller.jpg'),
                  'detailpage' => array('file' => dirname(__FILE__).'/../res/upload1.bin')));
        $this->assertNotNull($asset);
        $this->assertEqual('test/cb/cbf9f9f453acaba556e00b48951815da5611f975/index.xml', $asset);
        $this->assertTrue(file_exists(self::$BUCKET . 'cb/cbf9f9f453acaba556e00b48951815da5611f975/vw_golf_smaller.jpg'),
            'Original file was not written to file system.');
        $this->assertTrue(file_exists(self::$BUCKET . 'cb/cbf9f9f453acaba556e00b48951815da5611f975/upload1.jpg'),
            'detailpage rendition file was not written to file system.');
        $this->assertFalse(file_exists(self::$BUCKET . 'cb/cbf9f9f453acaba556e00b48951815da5611f975/upload1.bin'),
            'detailpage rendition file was written to file system with the wrong extension.');
        $this->assertTrue(file_exists(binarypool_config::getRoot() . $asset),
            'Asset file was not written to file system.');
    }
}
?>
