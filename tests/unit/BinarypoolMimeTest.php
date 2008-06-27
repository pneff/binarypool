<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/mime.php');

/**
 * Tests the binarypool_mime class.
 */
class BinarypoolMimeTest extends BinarypoolTestCase {
    /**
     * Tests MIME type detection for a JPG file.
     */
    function testMimeTypeJPG() {
        $this->assertEqual(
            binarypool_mime::getMimeType($this->testfile),
            'image/jpeg'
        );
    }
    
    /**
     * Tests MIME type detection for a Flash file.
     */
    function testMimeTypeSWF() {
        $this->assertEqual(
            binarypool_mime::getMimeType(realpath(dirname(__FILE__).'/../res/swiss-kurier.swf')),
            'application/x-shockwave-flash'
        );
    }
}
