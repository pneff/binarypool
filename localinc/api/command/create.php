<?php
require_once(dirname(__FILE__).'/../../binarypool/asset.php');
require_once(dirname(__FILE__).'/../../binarypool/exception.php');
require_once(dirname(__FILE__).'/../../binarypool/storage.php');
require_once(dirname(__FILE__).'/../../binarypool/views.php');
require_once(dirname(__FILE__).'/../../binarypool/config.php');

class api_command_create extends api_command_base {
    private static $UPLOAD_TYPES = array('IMAGE', 'MOVIE', 'XML');
    // Files that need to be cleaned up in the end.
    private $tmpfiles = array();
    
    public static $lastModified = null;
    
    protected function execute() {
        try {
            $this->upload($this->bucket);
        } catch (Exception $e) {
            $this->cleanup($this->tmpfiles);
            throw $e;
        }
        $this->cleanup($this->tmpfiles);
    }
    
    protected function upload($bucket) {
        $this->checkInput();
        
        // Get params
        $type = $this->request->getParam('Type');
        $callback = $this->request->getParam('Callback', '');
        $files = $this->getFiles();
        $url = $this->request->getParam('URL');
        $created = true;
        
        $storage = new binarypool_storage($bucket);
        
        // 304 not modified
        if (0 == count($files) && !empty($url)) {
            $symlink = binarypool_views::getDownloadedViewPath($bucket, $url);
            $asset = $storage->getAssetForLink($symlink);
            $this->log->info("Unmodified file %s", $asset);
            $created = false;
        } else {
            // Save file
            $asset = $storage->save($type, $files);
            $this->log->info("Created file %s", $asset);
        }
        
        foreach ($files as $rendition => $file) {
            unlink($file['file']);
        }
        
        if ($callback !== '') {
            $storage->addCallback($asset, $callback);
        }
        
        $metadata = array();
        $metadata['URL'] = $url;
        if ($created) {
            binarypool_views::created($bucket, $asset, $metadata);
        } else {
            $assetObj = $storage->getAssetObject($asset);
            binarypool_views::updated($bucket, $asset, $assetObj, $metadata);
        }
        
        $this->setResponseCode(201);
        $this->response->setHeader('Location', $asset);
        $this->response->setHeader('X-Asset', $asset);
        
        $xml = "<status method='post'><asset>" . htmlspecialchars($asset) . "</asset></status>";
        array_push($this->data, new api_model_xml($xml));
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
        
        if ( !self::$lastModified ) {
            self::$lastModified = new binarypool_lastmodified(); 
        }
        $lastmodified = self::$lastModified->lastModified($this->bucket, $url);
        
        if ( binarypool_config::getCacheRevalidate($this->bucket) === 0 ) {
            $lastmodified['time'] = 0;
        }
        
        $tmpfile = tempnam(sys_get_temp_dir(), 'binary');
        if ($tmpfile == '' || $tmpfile === FALSE) {
            throw new binarypool_exception(104, 500, "Could not create temporary file");
        }
        array_push($this->tmpfiles, $tmpfile);
        
        $result = array('code' => 0, 'headers' => array(), 'body' => ''); 
        $retries = 3;
        
        if ($lastmodified['revalidate']) {
            $httpc = new binarypool_httpclient();
            while ( $retries ) {
                try {
                    $result = $httpc->download($url, $tmpfile, $lastmodified['time']);
                    if ( $result['code'] < 500 ) { break; }
                } catch ( binarypool_httpclient_exception $e ) {
                    // ignore - dropped connections etc. - retry
                    $this->log->debug("Failed download attempt from %s: %s", $url, $e);
                }
                sleep(1);
                $retries--;
            }
        } else {
            $result['code'] = 304;
        }
        
        if ( 304 == $result['code'] ) {
            $this->log->debug("File %s has not been modified", $url);
            return array();
        }
        
        if ( $result['code'] != 200 || !filesize($tmpfile) ) {
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
        
        return array('_' => array(
            'file'     => $tmpfile,
            'filename' => $filename,
        ));
    }
    
    /**
     * Removes the temporary files created by this command.
     */
    protected function cleanup($files) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
