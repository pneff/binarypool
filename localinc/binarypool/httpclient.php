<?php
require_once(dirname(__FILE__) . '/config.php');

/*
 * HTTP Client wrapper around curl with methods
 * corresponding to http methods.
 * 
 * Used to fetch remote URL's - supports GET only
 * 
 * <pre>
 * $http_client = new bx_http_client();
 * $lastmodified = filemtime('/tmp/somepic.png');
 * print_r( $http_client->get('http://example.com/images/somepic.png', $lastmodified) );
 * </pre>
 * 
 * @author Harry Fuecks, local.ch
 * @since 2008-02-07
 */
class binarypool_httpclient {
    
    /* Time to make the initial connection*/
    public $connectionTimeout = 1;
    /* Time to complete the whole request */
    public $timeout = 10;
    
    /**
     * Prepare a curl handle for the http request
     *
     * @param String $url the URL to fetch
     * @param int $modified (optional) seconds since Unix epoch of local copy
     * @return $curl curl handle
     */
    protected function prepareCurl($url, $lastmodified = 0) {
        $curl = curl_init();
        
        // See http://curl.haxx.se/libcurl/c/curl_easy_setopt.html
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, binarypool_config::getUseragent());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  // return data as string 
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);  // don't follow redirects
        
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout); // can't connect? fail fast...
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout); // total timeout
        
        curl_setopt($curl, CURLOPT_NOSIGNAL,true);
        curl_setopt($curl, CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE);
        curl_setopt($curl, CURLOPT_FILETIME, TRUE);
        curl_setopt($curl, CURLOPT_HEADER, TRUE);
        curl_setopt($curl, CURLOPT_ENCODING, ''); // sends Accept-Encoding for all suported encodings
        
        if ( $lastmodified ) {
            curl_setopt($curl, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
            curl_setopt($curl, CURLOPT_TIMEVALUE, $lastmodified);
        }
        
        return $curl;
    }
    
    protected function executeCurl($curl) {
        if ( ($output = curl_exec($curl)) === false ) {
            throw new binarypool_httpclient_exception(curl_errno($curl), curl_error($curl));
        }
        
        $info = curl_getinfo($curl);
        $status = (isset($info['http_code'])) ? $info['http_code'] : 0;
        $header_size = (isset($info['header_size'])) ? $info['header_size'] : 0;
        
        $headersString = substr($output, 0, $header_size - 4);
        $output = substr($output, $header_size);
        
        // Put headers into associative array
        $headers = array();
        foreach (explode("\n", $headersString) as $headerLine) {
            if (($pos = strpos($headerLine, ":")) !== FALSE) {
                $key = trim(substr($headerLine, 0, $pos));
                $value = trim(substr($headerLine, $pos + 1));
                $headers[$key] = $value;
            }
        }
        
        return array('code' => intval($status),
                     'headers' => $headers,
                     'body' => $output);
    }
    
    /*
     * HTTP GET to $url
     * Optional $lastmodified is a Unix timestamp of a local
     * resource (e.g. a copy of the resource from $url, downloaded ealier)
     * 
     * Returns an array like;
     * array('code' => intval($status),
     *                'headers' => $headers,
     *                'body' => $output);
     * 
     * Warning: this method returns the body in memory - for big files
     * you may have memory problems
     * 
     * @param String $url
     * @param int $lastmodified for HTTP Last-Modified
     * @return array
     */
    public function get($url, $lastmodified = 0) {
        $curl = $this->prepareCurl($url, $lastmodified);
        return $this->executeCurl($curl);
    }

    /**
     * Download the URL to a local file.
     * 
     * @param String $url to download from
     * @param String $file tmp file to download to
     * @param int $lastmodified for HTTP Last-Modified
     */
    public function download($url, $file, $lastmodified = 0) {
        $f = fopen($file, "w");

        $curl = $this->prepareCurl($url, $lastmodified);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FILE, $f);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1200);

        $result = $this->executeCurl($curl);
        fclose($f);
        return $result;
    }
}

class binarypool_httpclient_exception extends api_exception {
    public function __construct($code, $message) {
        parent::__construct(self::THROW_FATAL, array(), $code, $message);
    }
}
