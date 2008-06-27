<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/config.php');

/**
 * Tests the binarypool_config class.
 */
class BinarypoolConfigTest extends BinarypoolTestCase {
    function testBuckets() {
        $buckets = binarypool_config::getBuckets();
        
        $this->assertTrue(isset($buckets['test']), "Test bucket is not defined in configuration.");
        $this->assertTrue(isset($buckets['test']['renditions']), "Test bucket does not have any renditions.");
    }

    function testGetPathOfConvert() {
        $convert = binarypool_config::getUtilityPath('convert');
        $this->assertNotEqual(0, strlen($convert));
    }
}
?>
