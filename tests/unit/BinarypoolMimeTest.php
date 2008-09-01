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
    
    /**
     * Test the size detection for JPEG files.
     */
    function testSizeJPG() {
        $info = binarypool_mime::getImageSize(realpath(dirname(__FILE__).'/../res/vw_golf.jpg'));
        $this->assertEqual($info['width'], '557');
        $this->assertEqual($info['height'], '344');
        $this->assertEqual($info['unit'], 'px');
    }
    
    /**
     * Test the size detection for PDF files.
     */
    function testSizePDF() {
        $info = binarypool_mime::getImageSize(realpath(dirname(__FILE__).'/../res/emil_frey_logo_2.pdf'));
        $this->assertEqual($info['width'], '203');
        $this->assertEqual($info['height'], '40');
        $this->assertEqual($info['unit'], 'mm');
    }
}
