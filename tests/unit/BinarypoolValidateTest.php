<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/validate.php');

/**
 * Tests the binarypool_validate class.
 */
class BinarypoolValidateTest extends BinarypoolTestCase {
    /**
     * Tests validate for XML.
     */
    function testValidateXML() {
        $this->expectException(new binarypool_exception(117, 400, "XML document is not well-formed."));
        $this->assertIdentical(
            binarypool_validate::validate('XML', 'test', realpath(dirname(__FILE__).'/../res/xmlfile-malformed.xml')),
            false);
    }
    
    /**
     * Tests validation of XSD for XML.
     */
    function testValidateXMLwithSchema() {
        $this->expectException(new binarypool_exception(118, 400, "XML document is not valid."));
        $this->assertIdentical(
            binarypool_validate::validate('XML', 'test', realpath(dirname(__FILE__).'/../res/xmlfile-invalid.xml')),
            false);
    }
    
    /**
     * Tests validation of XSD for XML for a valid file.
     */
    function testValidateXMLwithSchemaOK() {
        $this->assertIdentical(
            binarypool_validate::validate('XML', 'test', realpath(dirname(__FILE__).'/../res/xmlfile.xml')),
            true);
    }
    
    /**
     * Tests validation of MIME types for an image file.
     */
    function testValidateIMAGE() {
        $file = realpath(dirname(__FILE__).'/../res/swiss-kurier.swf');
        $this->expectException(new binarypool_exception(119, 400, "Invalid MIME type for image. Allowed types: image/jpeg"));
        $this->assertIdentical(
            binarypool_validate::validate('IMAGE', 'test_image_validation', $file),
            false);
    }
    
    /**
     * Tests validation of MIME types for an image file.
     */
    function testValidateIMAGEOkay() {
        $file = realpath(dirname(__FILE__).'/../res/vw_golf.jpg');
        $this->assertIdentical(
            binarypool_validate::validate('IMAGE', 'test_image_validation', $file),
            true);
    }
}
?>
