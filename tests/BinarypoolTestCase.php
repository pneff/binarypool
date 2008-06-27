<?php
require_once(dirname(__FILE__) . '/../localinc/binarypool/config.php');

if (! defined('API_PROJECT_DIR')) {
    define('API_PROJECT_DIR', realpath(dirname(__FILE__)."/../"));
}

/**
 * Base class for binary pool tests.
 */
abstract class BinarypoolTestCase extends UnitTestCase {
    protected static $BUCKET = '';
    protected $testfile = '';
    
    function setUp() {
        self::$BUCKET = binarypool_config::getRoot() . 'test/';
        
        // Remove bucket
        if (file_exists(self::$BUCKET)) {
            $this->deltree(self::$BUCKET);
        }
        
        // Remove trash
        if (file_exists(binarypool_config::getRoot() . 'Trash/')) {
            $this->deltree(binarypool_config::getRoot() . 'Trash/');
        }
        
        $this->testfile = realpath(dirname(__FILE__).'/res/vw_golf.jpg');
    }
    
    /**
     * Returns the first nodeValue from the given XPath
     * query.
     */
    protected function getXPathValue($xp, $query) {
        $res = $xp->query($query);
        $this->assertNotNull($res, "$query could not be executed.");
        if ($res == null) {
            return false;
        }

        $this->assertTrue($res->length > 0, "$query did not return any results.");
        if ($res->length <= 0) {
            return false;
        }

        $this->assertTrue($res->length == 1, "$query returned " . $res->length . " results. We expected only one.");
        return $res->item(0)->nodeValue;
    }

    /**
     * Asserts that an XPath returns the given value.
     *
     * @param $xp: DOMXPath object to test on
     * @param $query: The query to test
     * @param $value: Expected value
     */
    protected function assertXPath($xp, $query, $value) {
        $nodevalue = $this->getXPathValue($xp, $query);
        if ($nodevalue === false) {
            return;
        }

        $this->assertEqual($nodevalue, $value, "$query did not return '$value' but '" . $nodevalue . "'");
    }

    /**
     * Removes a directory recursively.
     */
    protected function deltree($path) {
        if (!is_link($path) && is_dir($path)) {
            $entries = scandir($path);
            foreach ($entries as $entry) {
                if ($entry != '.' && $entry != '..') {
                    $this->deltree($path.DIRECTORY_SEPARATOR.$entry);
                }
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }
}
?>
