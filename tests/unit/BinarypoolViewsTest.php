<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/config.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/storage.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/views.php');

/**
 * Tests the binarypool_views class which puts each binary
 * into the different views (by symlinking them).
 */
class BinarypoolViewsTest extends BinarypoolTestCase {
    /**
     * Test preparation: create a binary which can be used for view
     * testing.
     */
    function setUp() {
        parent::setUp();

        $storage = new binarypool_storage('test');
        $asset = $storage->save('IMAGE', array('_' => array('file' => $this->testfile)));
        $this->assertNotNull($asset);
        $this->assetFile = $asset;
        $this->assetId = '096dfa489bc3f21df56eded2143843f135ae967e';
    }
    
    /**
     * Tests if the binary is correctly put into a view for its
     * creation date.
     */
    function testCreationDateView() {
        $date = date('Y/m/d');
        $viewCreatedAt = self::$BUCKET . 'created/' . $date . '/' . $this->assetId;
        
        binarypool_views::created('test', $this->assetFile, array());
        $this->assertTrue(file_exists($viewCreatedAt),
            'Asset was not put into creation date view.');
        $this->assertTrue(file_exists($viewCreatedAt . '/index.xml'),
            'Asset file does not exist in creation date view.');
    }
    
    /**
     * Tests if the binary is correctly put into a view for its
     * expiry date.
     */
    function testExpireDateView() {
        $date = date('Y/m/d', strtotime('+7 days'));
        $viewExpiresAt = self::$BUCKET . 'expiry/' . $date . '/' . $this->assetId;
        
        binarypool_views::created('test', $this->assetFile, array());
        $this->assertTrue(file_exists($viewExpiresAt),
            'Asset was not put into expiration date view.');
        $this->assertTrue(file_exists($viewExpiresAt . '/index.xml'),
            'Asset file does not exist in expiration date view.');
    }

    /**
     * Checks that the view logic uses the information in the asset file
     * to determine an expiry date.
     */
    function testExpireDateViewWithAssetInformation() {
        $asset = new binarypool_asset(binarypool_config::getRoot() . $this->assetFile);
        $asset->setExpiry(strtotime('+2 days'));
        file_put_contents(binarypool_config::getRoot() . $this->assetFile, $asset->getXML());

        // All there?
        binarypool_views::created('test', $this->assetFile, array());
        $date = date('Y/m/d', strtotime('+2 days'));
        $viewExpiresAt = self::$BUCKET . 'expiry/' . $date . '/' . $this->assetId;
        $this->assertTrue(file_exists($viewExpiresAt),
            'Asset was not put into correct expiration date view.');
        $this->assertTrue(file_exists($viewExpiresAt . '/index.xml'),
            'Asset file does not exist in expiration date view.');
    }

    /**
     * Checks the correct behaviour when calling the view update function
     * without having changed the expiry date.
     */
    function testUpdateExpireDateViewUnchanged() {
        // Paths to assert against
        $date = date('Y/m/d', strtotime('+7 days'));
        $viewExpires = self::$BUCKET . 'expiry/' . $date . '/' . $this->assetId;

        // First view correctly created?
        binarypool_views::created('test', $this->assetFile, array());
        $this->assertTrue(file_exists($viewExpires),
            'Asset was not put into the expiration date view.');
        $this->assertTrue(file_exists($viewExpires . '/index.xml'),
            'Asset file does not exist in the expiration date view.');

        // Update
        $asset = new binarypool_asset(binarypool_config::getRoot() . $this->assetFile);

        // First view is kept around?
        binarypool_views::updated('test', $this->assetFile, $asset);
        $this->assertTrue(file_exists($viewExpires),
            'Asset was deleted from expiration date view.');
        $this->assertTrue(file_exists($viewExpires . '/index.xml'),
            'Asset file was deleted from expiration date view.');
    }

    /**
     * Checks the correct behaviour when updating an asset's expiry date.
     * The old symbolic link should be deleted and the new one created.
     */
    function testUpdateExpireDateView() {
        // Paths to assert against
        $date = date('Y/m/d', strtotime('+2 days'));
        $viewExpires2Days = self::$BUCKET . 'expiry/' . $date . '/' . $this->assetId;
        $date = date('Y/m/d', strtotime('+9 days'));
        $viewExpires9Days = self::$BUCKET . 'expiry/' . $date . '/' . $this->assetId;

        $asset = new binarypool_asset(binarypool_config::getRoot() . $this->assetFile);
        $asset->setExpiry(strtotime('+2 days'));
        file_put_contents(binarypool_config::getRoot() . $this->assetFile, $asset->getXML());

        // First view correctly created?
        binarypool_views::created('test', $this->assetFile, array());
        $this->assertTrue(file_exists($viewExpires2Days),
            'Asset was not put into the first expiration date view.');
        $this->assertTrue(file_exists($viewExpires2Days . '/index.xml'),
            'Asset file does not exist in the first expiration date view.');

        // Update
        $oldAsset = new binarypool_asset(binarypool_config::getRoot() . $this->assetFile);
        $asset = new binarypool_asset(binarypool_config::getRoot() . $this->assetFile);
        $asset->setExpiry(strtotime('+9 days'));
        file_put_contents(binarypool_config::getRoot() . $this->assetFile, $asset->getXML());

        // Second view correctly created?
        binarypool_views::updated('test', $this->assetFile, $oldAsset);
        $this->assertTrue(file_exists($viewExpires9Days),
            'Asset was not put into the new expiration date view.');
        $this->assertTrue(file_exists($viewExpires9Days . '/index.xml'),
            'Asset file does not exist in the new expiration date view.');

        // First view correctly deleted?
        binarypool_views::created('test', $this->assetFile, array());
        $this->assertFalse(file_exists($viewExpires2Days),
            'Asset was not deleted from the first expiration date view.');
    }
    
    /**
     * Tests if the binary is correctly put into a view based on the
     * URL is was fetched from
     */
    function testDownloadedView() {
        $url = 'http://staticlocal.ch/images/logo.gif';
        $urlhash ='2c76cf00527d36da0e2bd72071dbd9d480d97993';
        $symlink = sprintf("%sdownloaded/2c/2c76cf00527d36da0e2bd72071dbd9d480d97993", self::$BUCKET);
        
        binarypool_views::created('test', $this->assetFile, array('URL'=>$url));
        
        $this->assertTrue(file_exists($symlink),
            "Asset was not put into downloaded view: $symlink.");
        $this->assertTrue(file_exists($symlink . '/index.xml'),
            'Asset file does not exist in downloaded view.');
    }
    
    /**
     * Tests the flagBadUrl creates a symlink pointing to /dev/null
     */
    function testFlagBadUrl() {
        $url = 'http://staticlocal.ch/images/bad.gif';
        $urlhash ='55c79a345dd0ec066c1f6089c89e94478eaa2437';
        $symlink = sprintf("%sdownloaded/55/55c79a345dd0ec066c1f6089c89e94478eaa2437", self::$BUCKET);
        binarypool_views::flagBadUrl('test', $url);
        $this->assertEqual(readlink($symlink),'/dev/null',
            	'Symlink does not point to /dev/null');
    }
    
    
}
?>
