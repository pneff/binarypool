<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/profilelog.php');

/**
 * Testing binarypool_profilelog
 */
class BinarypoolProfilelogTest extends BinarypoolTestCase {
    
    function setUp() {
        $logfile = $this->getLogFileName();
        if ( file_exists($logfile) ) {
            file_put_contents($logfile, "");
        }
        binarypool_profilelog::$profilelogger = null;
    }
    
    function tearDown() {
        binarypool_profilelog::$profilelogger = null;
    }
    
    /**
     * Test we can put a message at the end of the profile log
     * and read it back
     */
    function testLogWrite() {
        $configs = api_config::getInstance()->profilelog;
        $logfile = $this->getLogFileName();
        $log = new binarypool_profilelog(); 
        $token = sha1("testLog".time());
        $log->info("$token");
        $lines = file($logfile);
        $this->assertPattern(
            "#$token#",
            array_pop($lines),
            "Unable to file token '$token' at end of logfile '$logfile'");
    }
    
    function getLogFileName() {
        $configs = api_config::getInstance()->profilelog;
        if ( isset($configs[0]) && isset($configs[0]['cfg']) ) {
            return $configs[0]['cfg'];
        }
        throw api_testing_exception("profilelog cannot be found in configuration");
    }
   
}