<?php
require_once(dirname(__FILE__) . "/../base/functional.php");

/**
 * Uploads a file and touches it to find out
 * if the expiry date is correctly updated.
 */
class test_func_touch extends test_base_functional {
    function testDefaultExpiry() {
        $this->upload();
        $date = date('Y/m/d', time() + ( 7 * 24 * 60 * 60 ));
        
        $response = $this->head('/test/expiry/' . $date . '/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 200);
    }
    
    /**
     * Touch the asset and make sure it stays at the
     * same location.
     */
    function testTouch() {
        $this->testDefaultExpiry();
        
        // Touch
        try {
            $this->post('/test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml', array());
        } catch ( api_testing_exception $e ) {
            // not expecting response
        }
        
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 204);
        $this->assertEqual('', $response->getContents());
        
        // Check
        $date = date('Y/m/d', time() + ( 7 * 24 * 60 * 60 ));
        $this->head('/test/expiry/' . $date . '/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 200);
    }
    
    /**
     * Touch the asset with a valid custom expiry date and make
     * sure the views are updated.
     */
    function testTouchWithCustomExpiryDate() {
        $this->testDefaultExpiry();
        
        // Touch with 3 days expiry date
        try {
            $this->post('/test/09/096dfa489bc3f21df56eded2143843f135ae967e/index.xml', array('TTL' => '3'));
        } catch ( api_testing_exception $e ) {
            // pass - not expecting response XML
        }
        
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 204);
        $this->assertEqual('', $response->getContents());
        
        // Check that old view is gone
        $date = date('Y/m/d', time() + ( 7 * 24 * 60 * 60 ));
        $this->head('/test/expiry/' . $date . '/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 404);

        // Check that new view is valid
        $date = date('Y/m/d', time() + ( 3 * 24 * 60 * 60 ));
        $this->head('/test/expiry/' . $date . '/096dfa489bc3f21df56eded2143843f135ae967e/index.xml');
        $response = api_response::getInstance();
        $this->assertEqual($response->getCode(), 200);
    }
}
