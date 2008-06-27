<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/config.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/browser.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/storage.php');
require_once(dirname(__FILE__).'/../../localinc/binarypool/views.php');

/**
 * Tests the binarypool_browser class.
 */
class BinarypoolBrowserTest extends BinarypoolTestCase {
    public function setUp() {
        parent::setUp();
        $this->storage = new binarypool_storage('test');
    }

    /**
     * The getExpired call should return an empty array if there
     * are no expired assets.
     */
    function testGetExpiredEmpty() {
        // Empty binarypool
        $this->assertEqual(array(), binarypool_browser::getExpired('test'));
        
        // Binarypool with one asset which has not been put in a view
        $asset = $this->storage->save('IMAGE', array('_' => array('file' => $this->testfile)));
        $this->assertEqual(array(), binarypool_browser::getExpired('test'));

        // Binarypool with one asset which has not expired
        binarypool_views::created('test', $asset);
        $this->assertEqual(array(), binarypool_browser::getExpired('test'));
    }

    /**
     * The getExpired call should return an asset which file expired
     * today.
     */
    function testGetExpiredToday() {
        $assetId = '096dfa489bc3f21df56eded2143843f135ae967e';
        $asset = $this->storage->save('IMAGE', array('_' => array('file' => $this->testfile)));

        $date = date('Y/m/d');
        $viewToday = self::$BUCKET . 'expiry/' . $date . '/' . $assetId;
        mkdir(dirname($viewToday), 0755, true);
        symlink(dirname(binarypool_config::getRoot() . $asset), $viewToday);

        $this->assertEqual(array('test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml'), binarypool_browser::getExpired('test'));
    }

    /**
     * The getExpired call should return an asset which file expired
     * yesterday.
     */
    function testGetExpiredYesterday() {
        $assetId = '096dfa489bc3f21df56eded2143843f135ae967e';
        $asset = $this->storage->save('IMAGE', array('_' => array('file' => $this->testfile)));

        $date = date('Y/m/d', strtotime('-1 days'));
        $viewToday = self::$BUCKET . 'expiry/' . $date . '/' . $assetId;
        
        mkdir(dirname($viewToday), 0755, true);
        symlink(dirname(binarypool_config::getRoot() . $asset), $viewToday);

        $this->assertEqual(array('test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml'), binarypool_browser::getExpired('test'));
    }
}
?>
