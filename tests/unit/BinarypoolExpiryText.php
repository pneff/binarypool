<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/expiry.php');

/**
 * Tests the binarypool_expiry class.
 */
class BinarypoolExpiryTest extends BinarypoolTestCase {
    /**
     * Create a test asset file.
     *
     * @param $callbacks: Array of callbacks to register.
     * @param $age: Age of item in days.
     */
    function createAsset($callbacks = array(), $age = 30) {
        // Create asset file
        $asset = new binarypool_asset();
        $asset->setBasePath('test/somehashhere');
        $asset->setOriginal($this->testfile);
        $asset->setExpiry(time() - ($age * 24 * 60 * 60));
        foreach ($callbacks as $callback) {
            $asset->addCallback($callback);
        }
        $xml = $asset->getXML();
        
        mkdir(self::$BUCKET . 'somehashhere', 0755, true);
        copy($this->testfile, self::$BUCKET . 'somehashhere/vw_golf.jpg');
        file_put_contents(self::$BUCKET . 'somehashhere/index.xml', $xml);
        
        return 'test/somehashhere/index.xml';
    }
    
    /**
     * Empty asset with 30 days old expiry date. Should be deleted.
     */
    function testExpiredEmptyAsset() {
        $asset = $this->createAsset();
        $this->assertTrue(binarypool_expiry::isExpired('test', $asset));
    }
    
    /**
     * Empty asset with an expiry date in the future. Must not be deleted.
     */
    function testExpiredEmptyAssetInFuture() {
        $asset = $this->createAsset(array(), -1);
        $this->assertFalse(binarypool_expiry::isExpired('test', $asset));
    }
    
    /**
     * Points to a non-existing callback. MUST not be expired.
     */
    function testAliveNonexistingCallback() {
        $asset = $this->createAsset(array('/tmp/absolutely_invalid_callback'));
        $this->assertFalse(binarypool_expiry::isExpired('test', $asset));
    }
    
    /**
     * Points to a callback with the wrong content. MUST not be expired.
     */
    function testAliveInvalidCallback1() {
        file_put_contents("/tmp/invalid_callback", "EXPIRED1");
        $asset = $this->createAsset(array('/tmp/invalid_callback'));
        $this->assertFalse(binarypool_expiry::isExpired('test', $asset));
    }

    /**
     * Points to a callback with the wrong content. MUST not be expired.
     */
    function testAliveInvalidCallback2() {
        file_put_contents("/tmp/invalid_callback", "OK");
        $asset = $this->createAsset(array('/tmp/invalid_callback'));
        $this->assertFalse(binarypool_expiry::isExpired('test', $asset));
    }

    /**
     * Points to a callback with the wrong content. MUST not be expired.
     */
    function testAliveInvalidCallback3() {
        file_put_contents("/tmp/invalid_callback", "expired");
        $asset = $this->createAsset(array('/tmp/invalid_callback'));
        $this->assertFalse(binarypool_expiry::isExpired('test', $asset));
    }

    /**
     * Points to a callback with the wrong content. MUST not be expired.
     */
    function testAliveInvalidCallback4() {
        file_put_contents("/tmp/invalid_callback", " EXPIRED");
        $asset = $this->createAsset(array('/tmp/invalid_callback'));
        $this->assertFalse(binarypool_expiry::isExpired('test', $asset));
    }

    /**
     * Points to a callback with the wrong content. MUST not be expired.
     */
    function testAliveInvalidCallbackURL() {
        $asset = $this->createAsset(array('http://www.trunk.local.ch/static/ibc'));
        $this->assertFalse(binarypool_expiry::isExpired('test', $asset));
    }
    
    /**
     * Points to a valid callback file. Must be expired.
     */
    function testExpiredWithCallback() {
        file_put_contents("/tmp/deleteme_callback", "EXPIRED");
        
        $asset = $this->createAsset(array('/tmp/deleteme_callback'));
        $this->assertTrue(binarypool_expiry::isExpired('test', $asset));
    }
}
?>
