<?php
require_once(dirname(__FILE__).'/../../binarypool/asset.php');
require_once(dirname(__FILE__).'/../../binarypool/exception.php');
require_once(dirname(__FILE__).'/../../binarypool/storage.php');
require_once(dirname(__FILE__).'/../../binarypool/storage_driver_file.php');
require_once(dirname(__FILE__).'/../../binarypool/views.php');
require_once(dirname(__FILE__).'/../../binarypool/config.php');

class api_command_create extends api_command_base {
    private static $UPLOAD_TYPES = array('IMAGE', 'MOVIE', 'XML');
    
    protected function execute() {
        $this->upload($this->bucket);
    }
    
    protected function upload($bucket) {
        $this->checkInput();
        
        // Get params
        $type = $this->request->getParam('Type');
        $callback = $this->request->getParam('Callback', '');
        $files = $this->getFiles();
        $url = $this->request->getParam('URL');
        $metadata = array();
        $metadata['URL'] = $url;
        
        // 304 not modified
        if ( 0 == count($files) && !empty($url) ) {
            
            $symlink = binarypool_views::getDownloadedViewPath($bucket, $url);
            $asset = str_replace('../..', $bucket, readlink($symlink)). '/index.xml';
            $this->log->info("Unmodified file %s", $asset);
            
        } else {
        
            // Save file
            $storage = new binarypool_storage($bucket);
            $asset = $storage->save($type, $files);
            $this->log->info("Created file %s", $asset);
        
        }
        
        $asset_store = new binarypool_storage_driver_file();
        $absStoragePath = $asset_store->absolutize($asset);
        
        foreach ($files as $rendition => $file) {
            unlink($file['file']);
        }
        
        binarypool_views::created($bucket, $asset, $metadata);
        
        // Add callback
        if ($callback != '') {
            $assetObj = new binarypool_asset($absStoragePath);
            $assetObj->addCallback($callback);
            file_put_contents($absStoragePath, $assetObj->getXML());
        }
        
        $this->setResponseCode(201);
        $this->response->setHeader('Location', $asset);
        $this->response->setHeader('X-Asset', $asset);
        
        $xml = "<status method='post'><asset>" . htmlspecialchars($asset) . "</asset></status>";
        array_push($this->data, new api_model_xml($xml));
        array_push($this->data, new api_model_file($absStoragePath));
    }

    protected function checkInput() {
        if (!$this->request->getParam('URL') && (!isset($_FILES['File']) || $_FILES['File']['tmp_name'] == '')) {
            throw new binarypool_exception(109, 400, "No file uploaded.");
        }
        
        $type = $this->request->getParam('Type');
        if (!$type) {
            throw new binarypool_exception(110, 400, "Type param not given.");
        }
        if (!in_array($type, self::$UPLOAD_TYPES)) {
            throw new binarypool_exception(111, 400, "Invalid upload type: " . $this->request->getParam('Type'));
        }
        
        $url = $this->request->getParam('URL');
        if ( $url ) {
            if ( !Validate::uri($url, array('allowed_schemes' => array('http', 'https'))) ) {
                throw new binarypool_exception(120, 400, "Invalid URL for download: " . $url);
            }
        }
    }
        
    /**
     * Return the files uploaded by the user.
     *
     * Returns a hash of hashes.
     *   - One element per rendition. The name for the original is '_'.
     *   - For each rendition the following two values:
     *       - file - Path to the file on the file system
     *       - filename - Filename part of the file
     */
    protected function getFiles() {
        if (isset($_FILES['File'])) {
            return $this->getFilesUploaded();
        } else if ($this->request->getParam('URL')) {
            return $this->getFilesFromUrl();
        } else {
            throw new binarypool_exception(109, 400, "No file uploaded.");
        }
    }
    
    protected function getFilesUploaded() {
        $retval = array();
        foreach ($_FILES as $key => $file) {
            if (strpos($key, 'File') === FALSE) {
                $log = new api_log();
                $log->err("Invalid file uploaded: $key");
            } else {
                $rendition = $key == 'File' ? '_' : str_replace('File_', '', $key);
                $retval[$rendition] = array(
                    'file'     => $file['tmp_name'],
                    'filename' => $file['name'],
                );
            }
        }
        return $retval;
    }
    
    protected function getFilesFromUrl() {
        $url = $this->request->getParam('URL');
        $this->log->debug("Downloading file: %s", $url);
        
        $httpc = new binarypool_httpclient();
        $lastmodified = $this->getURLLastModified($url);
        $result = array('code' => 0, 'headers' => array(), 'body' => ''); 
        $retries = 3;
        
        while ( $retries ) {
            try {
                $result = $httpc->get($url, $lastmodified);
                if ( $result['code'] < 500 ) { break; }
            } catch ( binarypool_httpclient_exception $e ) {
                // ignore - dropped connections etc. - retry
            }
            sleep(1);
            $retries--;
        }
        
        if ( 304 == $result['code'] ) {
            $this->log->debug("File %s has not been modified", $url);
            return array();
        }
        
        if ( $result['code'] != 200 || empty($result['body']) ) {
            binarypool_views::flagBadUrl($this->bucket, $url);
            throw new binarypool_exception(121, 400, "File could not be fetched from URL: " . $url);
        }
        
        $url_parsed = parse_url($url);
        $filename = basename($url_parsed['path']);
        
        # Restrict filenames TO ALPHANUMS and reduce sequences of '.' to avoid
        # traversal issues, unicode issues, command injection etc.
        $filename = preg_replace(
                                array('#\.{2,}#', '#[^a-zA-Z0-9\.]+#'),
                                array('.','_'),
                                $filename
                                );
        
        $file = tempnam(sys_get_temp_dir(), 'binary');
        if ($file == '' || $file === FALSE) {
            throw new binarypool_exception(104, 500, "Could not create temporary file");
        }
        
        file_put_contents($file, $result['body']);
        
        return array('_' => array(
            'file'     => $file,
            'filename' => $filename,
        ));
    }
    
    /**
     * Looks in the URL view for a symlink matching the provided
     * URL and if found, returns the mtime of the link target
     * 
     * If the link points to /dev/null, we have a URL we were unable
     * to download before - if that symlink itself is older than 1 hour
     * we delete it and return 0. If the symlink is younger than hour,
     * we raise an exception and abort. Intended as mechanism to prevent
     * repeated attempts to download the same (non 200) URL in a short
     * time period
     *
     * @param String $url
     * @return int Unix timestamp (0 means not found)
     */
    protected function getURLLastModified($url) {
        $symlink = binarypool_views::getDownloadedViewPath($this->bucket, $url);
        
        // Check the link target exists - filemtime would raise a PHP
        // WARNING if not exists
        if ( file_exists($symlink) ) {
            
            if ( readlink($symlink) == '/dev/null' ) {
                
                $stat = lstat($symlink);
                $now = time();
                $failed_time = $now - $stat['mtime'];
                
                if ( $failed_time > binarypool_config::getBadUrlExpiry() ) {
                    unlink($symlink);
                    return 0;
                }
                
                $failed_nextfetch = ($stat['mtime'] + binarypool_config::getBadUrlExpiry()) - $now;
                throw new binarypool_exception( 122, 400, "File download failed $failed_time seconds ago. Re-fetching allowed in next time in $failed_nextfetch seconds: $url");
                
            }
            // return mtime for the link target
            return filemtime($symlink);
        }
        
        return 0;
    }
}