<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/validate_image.php');

/**
 * Tests the binarypool_validate_image class.
 */
class BinarypoolValidateIMAGETest extends BinarypoolTestCase {
    function testNoParams() {
        $file = realpath(dirname(__FILE__).'/../res/vw_golf.jpg');
        $this->assertIdentical(binarypool_validate_image::validate($file), true);
    }
    
    function testNullParam() {
        $file = realpath(dirname(__FILE__).'/../res/vw_golf.jpg');
        $this->assertIdentical(binarypool_validate_image::validate($file, null), true);
    }
    
    function testEmptyParam() {
        $file = realpath(dirname(__FILE__).'/../res/vw_golf.jpg');
        $this->assertIdentical(binarypool_validate_image::validate($file, array()), true);
    }
    
    function testEmptyMimeTypesList() {
        $file = realpath(dirname(__FILE__).'/../res/vw_golf.jpg');
        $this->assertIdentical(binarypool_validate_image::validate($file, array('mime' => array())), true);
    }
    
    function testInvalidMimeTypeParam() {
        $file = realpath(dirname(__FILE__).'/../res/vw_golf.jpg');
        $this->assertIdentical(binarypool_validate_image::validate($file, array('mime' => 'test')), true);
    }
    
    function testMimeTypeJpegOkay() {
        $file = realpath(dirname(__FILE__).'/../res/vw_golf.jpg');
        $this->assertIdentical(binarypool_validate_image::validate($file, array('mime' => array('image/jpeg'))), true);
    }
    
    function testMimeTypeJpegInvalid() {
        $file = realpath(dirname(__FILE__).'/../res/swiss-kurier.swf');
        $this->expectException(new binarypool_exception(119, 400, "Invalid MIME type for image. Allowed types: image/jpeg"));
        $this->assertIdentical(binarypool_validate_image::validate($file, array('mime' => array('image/jpeg'))), false);
    }
    
    function testMimeTypeMultipleInvalid() {
        $file = realpath(dirname(__FILE__).'/../res/swiss-kurier.swf');
        $this->expectException(new binarypool_exception(119, 400, "Invalid MIME type for image. Allowed types: image/jpeg, image/gif"));
        $this->assertIdentical(binarypool_validate_image::validate($file,
            array('mime' => array('image/jpeg', 'image/gif'))),
            false);
    }
}
?>
