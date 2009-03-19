<?php
/**
 * Abstracts away the difference between local and remove files.
 * Downloads remote files into a cached local file if not available yet.
 */
class binarypool_fileobject {
    public $file = null;
    protected $http_client = null;
    protected $remote = false;
    protected $origfile = null;
    protected static $REMOTE_PROTOCOLS = array('http://', 'https://');
    private $exists = False;
    
    public function __construct($file, $http_client = null) {
        $this->file = $this->origfile = $file;
        $this->http_client = $http_client;
        if (is_null($http_client)) {
            $this->http_client = new binarypool_httpclient();
        }
        
        foreach (self::$REMOTE_PROTOCOLS as $proto) {
            if (strpos($file, $proto) === 0) {
                $this->remote = true;
                break;
            }
        }
        
        if ($this->remote) {
            $this->file = $this->downloadFile($file);
        }
        
        $this->exists = file_exists($this->file);
        
    }
    
    public function isRemote() {
        return $this->remote;
    }
    
    public static function forgetCache($url) {
        $tmpfile = self::getTempfile($url);
        if (file_exists($tmpfile)) {
            unlink($tmpfile);
        }
        // Try to remove the parent directory as well.
        // Will fail silently if there is still something in there
        @rmdir(dirname($tmpfile));
    }
    
    /**
     * Whether the file which this fileobject represents exists on
     * the local filesystem (as a temporary file)
     */
    public function exists() {
        return $this->exists;
    }
    
    protected function downloadFile($url) {
        $tmpfile = $this->getTempfile($url);
        if (!file_exists($tmpfile)) {
            $result = $this->http_client->download($url, $tmpfile);
            if ($result['code'] !== 200) {
                if ( file_exists($tmpfile) ) {
                    unlink($tmpfile);
                }
                return null;
            }
        }
        return $tmpfile;
    }
    
    protected static function getTempfile($url) {
        $hash = sha1($url);
        $tmpdir = sys_get_temp_dir() . '/binarypool-fileobject/' .
            substr($hash, 0, 2);
        if (!file_exists($tmpdir)) {
            mkdir($tmpdir, 0755, true);
        }
        return $tmpdir . '/' . $hash;
    }
}
