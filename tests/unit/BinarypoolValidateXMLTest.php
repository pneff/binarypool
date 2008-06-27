<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/validate_xml.php');

/**
 * Tests the binarypool_validate_xml class.
 */
class BinarypoolValidateXMLTest extends BinarypoolTestCase {
    /**
     * Make sure a well-formed XML document validates.
     */
    function testWellformedOK() {
        $file = realpath(dirname(__FILE__).'/../res/xmlfile.xml');
        $this->assertIdentical(binarypool_validate_xml::validate($file), true);
    }
    
    /**
     * Make sure a not well-formed XML document does not validate.
     */
    function testWellformedError() {
        $file = realpath(dirname(__FILE__).'/../res/xmlfile-malformed.xml');
        $this->expectException(new binarypool_exception(117, 400, "XML document is not well-formed."));
        $this->assertIdentical(binarypool_validate_xml::validate($file), false);
    }
    
    /**
     * Test if an XML file is valid according to a schema.
     */
    function testValidOK() {
        $file = realpath(dirname(__FILE__).'/../res/xmlfile.xml');
        $this->assertIdentical(binarypool_validate_xml::validate($file,
                dirname(__FILE__) . '/../../conf/schema/localinfo-2.0.xsd'),
            true);
    }
    
    /**
     * Make sure an invalid XML file (according to the schema) does not
     * validate.
     */
    function testValidError() {
        $file = realpath(dirname(__FILE__).'/../res/xmlfile-invalid.xml');
        $this->expectException(new binarypool_exception(118, 400, "XML document is not valid."));
        $this->assertIdentical(binarypool_validate_xml::validate($file,
                dirname(__FILE__) . '/../../conf/schema/localinfo-2.0.xsd'),
            false);
    }
}
?>
