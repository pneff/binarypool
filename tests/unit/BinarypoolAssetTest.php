<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/asset.php');

/**
 * Tests the binarypool_asset class.
 */
class BinarypoolAssetTest extends BinarypoolTestCase {
    /**
     * Create an asset XML for a file with no renditions.
     */
    function testAssetWithoutRenditions() {
        // Create asset file
        $asset = new binarypool_asset($this->getDummyStorage());
        $asset->setBasePath('test/somehashhere');
        $asset->setOriginal($this->testfile);
        $xml = $asset->getXML();
        
        // Load XML
        $dom = DOMDocument::loadXML($xml);
        $xp = new DOMXPath($dom);
        
        // Test
        $this->assertXPath($xp, '/registry/@version', '3.0');
        $this->assertXPath($xp, '/registry/items/item/@type', 'IMAGE');
        $this->assertXPath($xp, '/registry/items/item/@isRendition', 'false');
        $this->assertXPath($xp, '/registry/items/item/webobject/@unit', 'px');
        $this->assertXPath($xp, '/registry/items/item/webobject/objectWidth', '557');
        $this->assertXPath($xp, '/registry/items/item/webobject/objectHeight', '344');
        $this->assertXPath($xp, '/registry/items/item/imageinfo/@isLandscape', 'true');
        $this->assertXPath($xp, '/registry/items/item/imageinfo/@unit', 'px');
        $this->assertXPath($xp, '/registry/items/item/imageinfo/width', '557');
        $this->assertXPath($xp, '/registry/items/item/imageinfo/height', '344');
        $this->assertXPath($xp, '/registry/items/item/location', 'test/somehashhere/vw_golf.jpg');
        $this->assertXPath($xp, '/registry/items/item/mimetype', 'image/jpeg');
        $this->assertXPath($xp, '/registry/items/item/size', '106237');
    }

    /**
     * Create an asset XML for a file with 1 rendition.
     */
    function testAssetWithRendition90x90() {
        $basepath = self::$BUCKET . 'somehashhere/';
        mkdir($basepath, 0755, true);
        
        // Copy original
        copy($this->testfile, $basepath . 'vw_golf.jpg');
        
        // Resize image
        $resizedFile = $basepath . 'resultlist';
        $out = binarypool_render_image::render($basepath . 'vw_golf.jpg', $resizedFile,
            null, array('width' => 90, 'height' => 90));
        $this->assertEqual($out, $resizedFile . '.jpg', 'Rendering did not determine the correct file extension for the thumbnail. - %s');
        
        // Create asset file
        $asset = new binarypool_asset($this->getDummyStorage());
        $asset->setBasePath('test/somehashhere');
        $asset->setOriginal($basepath . 'vw_golf.jpg');
        $asset->setRendition('resultlist', $out);
        $asset->setExpiry(time()+1000);
        $this->assertEqual('096dfa489bc3f21df56eded2143843f135ae967e', $asset->getHash());
        $this->assertWithinMargin(time(), $asset->getCreated(), 10);
        $this->assertWithinMargin(time()+1000, $asset->getExpiry(), 10);
        $xml = $asset->getXML();
        file_put_contents($basepath . 'index.xml', $xml);
        
        // Load XML
        $dom = DOMDocument::loadXML($xml);
        $xp = new DOMXPath($dom);
        
        $res = $xp->query('/registry/items/item');
        $this->assertEqual($res->length, 2);
        
        // Test original file
        $this->assertXPath($xp, '/registry/@version', '3.0');
        $this->assertXPath($xp, '/registry/items/item[1]/@type', 'IMAGE');
        $this->assertXPath($xp, '/registry/items/item[1]/@isRendition', 'false');
        $this->assertXPath($xp, '/registry/items/item[1]/webobject/objectWidth', '557');
        $this->assertXPath($xp, '/registry/items/item[1]/webobject/objectHeight', '344');
        $this->assertXPath($xp, '/registry/items/item[1]/imageinfo/@isLandscape', 'true');
        $this->assertXPath($xp, '/registry/items/item[1]/imageinfo/width', '557');
        $this->assertXPath($xp, '/registry/items/item[1]/imageinfo/height', '344');
        $this->assertXPath($xp, '/registry/items/item[1]/location', 'test/somehashhere/vw_golf.jpg');
        $this->assertXPath($xp, '/registry/items/item[1]/mimetype', 'image/jpeg');
        $this->assertXPath($xp, '/registry/items/item[1]/size', '106237');
        
        // Test rendition
        $this->assertXPath($xp, '/registry/items/item[2]/@type', 'IMAGE');
        $this->assertXPath($xp, '/registry/items/item[2]/@isRendition', 'true');
        $this->assertXPath($xp, '/registry/items/item[2]/webobject/objectWidth', '90');
        $this->assertXPath($xp, '/registry/items/item[2]/webobject/objectHeight', '56');
        $this->assertXPath($xp, '/registry/items/item[2]/imageinfo/@isLandscape', 'true');
        $this->assertXPath($xp, '/registry/items/item[2]/imageinfo/width', '90');
        $this->assertXPath($xp, '/registry/items/item[2]/imageinfo/height', '56');
        $this->assertXPath($xp, '/registry/items/item[2]/rendition', 'resultlist');
        $this->assertXPath($xp, '/registry/items/item[2]/location', 'test/somehashhere/resultlist.jpg');
        $this->assertXPath($xp, '/registry/items/item[2]/mimetype', 'image/jpeg');
        $this->assertXPath($xp, '/registry/items/item[2]/size', filesize($out));
        
        // Test attributes
        $created = intval($this->getXPathValue($xp, '/registry/created'));
        $this->assertWithinMargin(time(), $created, 10);
        $expiry = intval($this->getXPathValue($xp, '/registry/expiry'));
        $this->assertWithinMargin(time()+1000, $expiry, 10);
    }

    /**
     * Load a created asset file.
     */
    function testLoadAssetFile() {
        $this->testAssetWithRendition90x90();
        $created = time();
        
        $basepath = 'test/somehashhere/';
        $basepathAbs = binarypool_config::getRoot() . $basepath;
        $assetFile = $basepath . 'index.xml';
        
        $asset = new binarypool_asset($this->getDummyStorage(), $assetFile);
        $this->assertEqual('test/somehashhere/', $asset->getBasePath());
        $this->assertEqual($basepathAbs . 'vw_golf.jpg', $asset->getOriginal());
        $this->assertEqual($basepathAbs . 'resultlist.jpg', $asset->getRendition('resultlist'));
        $this->assertEqual(array('resultlist' => $basepathAbs . 'resultlist.jpg'), $asset->getRenditions());
        $this->assertEqual('096dfa489bc3f21df56eded2143843f135ae967e', $asset->getHash());
        $this->assertWithinMargin($created, $asset->getCreated(), 10);
        $this->assertEqual('IMAGE', $asset->getType());
    }
    
    /**
     * Load the asset file from storage.
     */
    function testLoadAssetFileFromStorage() {
        $this->testAssetWithRendition90x90();
        $storage = new binarypool_storage('test');
        $basepath = 'test/somehashhere/';
        $basepathAbs = binarypool_config::getRoot() . $basepath;
        
        $asset = $storage->getAssetObject($basepath . 'index.xml');
        
        $this->assertEqual('test/somehashhere/', $asset->getBasePath());
        $this->assertEqual($basepathAbs . 'vw_golf.jpg', $asset->getOriginal());
        $this->assertEqual($basepathAbs . 'resultlist.jpg', $asset->getRendition('resultlist'));
        $this->assertEqual(array('resultlist' => $basepathAbs . 'resultlist.jpg'), $asset->getRenditions());
        $this->assertEqual('096dfa489bc3f21df56eded2143843f135ae967e', $asset->getHash());
    }

    /**
     * Try out if the output from a loaded asset file is identical
     * to the original.
     */
    function testRecreateAssetFile() {
        $this->testAssetWithRendition90x90();
        
        $basepath = 'test/somehashhere/';
        $assetFile = $basepath . 'index.xml';
        
        // Get old XML DOM
        $oldDom = DOMDocument::load(binarypool_config::getRoot() . $assetFile);
        
        // Get new XML DOM
        $asset = new binarypool_asset($this->getDummyStorage(), $assetFile);
        $xml = $asset->getXML();
        $newDom = DOMDocument::loadXML($xml);
        
        // Compare
        $this->assertEqual($newDom->saveXML(), $oldDom->saveXML());
    }
    
    /**
     * Set a callback on an asset file.
     */
    function testAssetfileCallback() {
        $this->testAssetWithRendition90x90();
        $assetFile = 'test/somehashhere/index.xml';
        
        // Load and check that no callbacks are around
        $asset = new binarypool_asset($this->getDummyStorage(), $assetFile);
        $this->assertEqual($asset->getCallbacks(), array());
        
        // Add a callback and check it's around
        $asset->addCallback('http://testing_this.local.ch/');
        $this->assertEqual($asset->getCallbacks(), array('http://testing_this.local.ch/'));
        
        // Check it's in the XML
        $xml = $asset->getXML();
        $dom = DOMDocument::loadXML($xml);
        $xp = new DOMXPath($dom);
        $this->assertXPath($xp, '/registry/callback', 'http://testing_this.local.ch/');
    }

    /**
     * Set a callback on an asset file. Checks that the same callback
     * can't be added twice.
     */
    function testAssetfileDuplicateCallback() {
        $asset = new binarypool_asset($this->getDummyStorage());
        $this->assertEqual($asset->getCallbacks(), array());
        
        // Add a callback and check it's around
        $asset->addCallback('http://testing_this.local.ch/');
        $this->assertEqual($asset->getCallbacks(), array('http://testing_this.local.ch/'));
        
        // Add 2nd time
        $asset->addCallback('http://testing_this.local.ch/');
        $this->assertEqual($asset->getCallbacks(), array('http://testing_this.local.ch/'));
    }

    /**
     * Tests that callbacks are loaded from the XML.
     */
    function testAssetfileCallbackLoadFile() {
        $this->testAssetWithRendition90x90();
        $assetFile = 'test/somehashhere/index.xml';
        
        // Add callbacks
        $asset = new binarypool_asset($this->getDummyStorage(), $assetFile);
        $asset->addCallback('http://testing_this.local.ch/');
        $asset->addCallback('http://testing_this.local.ch/2');
        
        // Save
        $xml = $asset->getXML();
        file_put_contents(binarypool_config::getRoot() . $assetFile, $xml);

        // Check that callbacks are loaded again
        $asset = new binarypool_asset($this->getDummyStorage(), $assetFile);
        $this->assertEqual($asset->getCallbacks(), array(
            'http://testing_this.local.ch/',
            'http://testing_this.local.ch/2'
        ));
    }

    /**
     * Adds add callback to an asset file which already has some.
     */
    function testAssetfileCallbackAddToExisting() {
        $this->testAssetfileCallbackLoadFile();
        $assetFile = 'test/somehashhere/index.xml';
        
        $asset = new binarypool_asset($this->getDummyStorage(), $assetFile);
        $this->assertEqual(2, count($asset->getCallbacks()));
        $asset->addCallback('http://testing_this.local.ch/falsetest');
        $this->assertEqual(3, count($asset->getCallbacks()));
        
        // Save
        $xml = $asset->getXML();
        file_put_contents(binarypool_config::getRoot() . $assetFile, $xml);

        // Check that callbacks are loaded again
        $asset = new binarypool_asset($this->getDummyStorage(), $assetFile);
        $this->assertEqual($asset->getCallbacks(), array(
            'http://testing_this.local.ch/',
            'http://testing_this.local.ch/2',
            'http://testing_this.local.ch/falsetest',
        ));
    }

    /**
     * Create an asset XML with an absolute base path.
     */
    function testAssetWithAbsoluteBasePath() {
        $asset = new binarypool_asset($this->getDummyStorage());
        $asset->setBasePath('http://bin.staticlocal.ch/', true);
        $asset->setOriginal('http://bin.staticlocal.ch/vw_golf.jpg');
        $xml = $asset->getXML();
        
        // Load XML
        $dom = DOMDocument::loadXML($xml);
        $xp = new DOMXPath($dom);
        
        // Test
        $this->assertXPath($xp, '/registry/@version', '3.0');
        $this->assertXPath($xp, '/registry/items/item/location', 'http://bin.staticlocal.ch/vw_golf.jpg');
        $this->assertXPath($xp, '/registry/items/item/location/@absolute', 'true');
        return $asset;
    }

    /**
     * Create an asset XML with an absolute base path and reload it.
     */
    function testAssetPersistanceWithAbsoluteBasePath() {
        $basepath = 'test/somehashhere/';
        $absBasepath = binarypool_config::getRoot() . $basepath;
        mkdir($absBasepath, 0755, true);
        $asset = $this->testAssetWithAbsoluteBasePath();
        file_put_contents($absBasepath . 'index.xml', $asset->getXML());
        
        // Load XML from FS
        $asset = new binarypool_asset($this->getDummyStorage(), $basepath . 'index.xml');
        $xml = $asset->getXML();
        $dom = DOMDocument::loadXML($xml);
        $xp = new DOMXPath($dom);

        // Test that the same information is still around
        $this->assertXPath($xp, '/registry/@version', '3.0');
        $this->assertXPath($xp, '/registry/items/item/location', 'http://bin.staticlocal.ch/vw_golf.jpg');
        $this->assertXPath($xp, '/registry/items/item/location/@absolute', 'true');
        $this->assertXPath($xp, '/registry/items/item/mimetype', 'image/jpeg');
        $this->assertXPath($xp, '/registry/items/item/size', '51941');
        return $asset;
    }

    function testPDFAssetWithoutRenditions() {
        // Create asset file
        $asset = new binarypool_asset($this->getDummyStorage());
        $asset->setBasePath('test/somehashhere');
        $asset->setOriginal(realpath(dirname(__FILE__).'/../res/emil_frey_logo_2.pdf'));
        $xml = $asset->getXML();
        
        // Load XML
        $dom = DOMDocument::loadXML($xml);
        $xp = new DOMXPath($dom);
        
        // Test
        $this->assertXPath($xp, '/registry/@version', '3.0');
        $this->assertXPath($xp, '/registry/items/item/@type', 'IMAGE');
        $this->assertXPath($xp, '/registry/items/item/@isRendition', 'false');
        $this->assertXPath($xp, '/registry/items/item/webobject/@unit', 'mm');
        $this->assertXPath($xp, '/registry/items/item/webobject/objectWidth', '203');
        $this->assertXPath($xp, '/registry/items/item/webobject/objectHeight', '40');
        $this->assertXPath($xp, '/registry/items/item/imageinfo/@isLandscape', 'true');
        $this->assertXPath($xp, '/registry/items/item/imageinfo/@unit', 'mm');
        $this->assertXPath($xp, '/registry/items/item/imageinfo/width', '203');
        $this->assertXPath($xp, '/registry/items/item/imageinfo/height', '40');
        $this->assertXPath($xp, '/registry/items/item/location', 'test/somehashhere/emil_frey_logo_2.pdf');
        $this->assertXPath($xp, '/registry/items/item/mimetype', 'application/pdf');
        $this->assertXPath($xp, '/registry/items/item/size', '28216');
    }
}
?>
