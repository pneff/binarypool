<?php
/**
 * Exception which includes an error code and a HTTP
 * status code to be sent.
 *
 * Currently used exception codes:
 *   - 100: Bucket not defined
 */
class binarypool_exception extends Exception {
    protected $httpcode = 500;
    
    function binarypool_exception($code, $httpcode, $msg) {
        $this->code = $code;
        $this->httpcode = $httpcode;
        $this->message = $msg;
    }
    
    public function getHttpCode() {
        return $this->httpcode;
    }
}
?>
