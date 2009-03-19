<?php
require_once(dirname(__FILE__) . '/storage_driver.php');
require_once(dirname(__FILE__) . '/../../inc/S3/Wrapper.php');

/**
 * A storage implementation to save files into Amazon S3.
 */
class binarypool_storage_driver_s3 extends binarypool_storage_driver {
    
    /**
     * Keep track of results for isFile() for the current request 
     */
    private static $isFileCache = array();
    
    /**
     * Keep track of results for isDir() for the current request
     */
    private static $isDirCache = array();
    
    public function __construct($cfg, $client = null, $cache = null, $time = null) {
        $this->cfg = $cfg;
        $this->cache = is_null($cache) ? api_cache::getInstance() : $cache;
        $this->time = is_null($time) ? time() : $time;
        
        if (is_null($client)) {
            $this->client = new S3_Wrapper($cfg['access_id'], $cfg['secret_key']);
        } else {
            $this->client = $client;
        }
    }

    public function absolutize($file) {
        return $this->cfg['base_url'] . ltrim($file, '/');
    }

    public function save($local_file, $remote_file) {
        $url = $this->absolutize($remote_file);

        // Cache the fileinfo
        binarypool_fileinfo::setCache($url,
            binarypool_fileinfo::getFileinfo($local_file));

        $this->removeCache($remote_file);
        $this->flushCache($remote_file);
        $retval = $this->client->putObjectFile(
            $local_file,
            $this->cfg['bucket'],
            $remote_file,
            S3::ACL_PUBLIC_READ,
            array(),
            binarypool_mime::getMimeType($local_file)
        );
        
        if ($retval === false) {
            throw new binarypool_exception(105, 500, "Could not copy file to its final destination on S3: $remote_file");
        }
        return $retval;
    }

    public function getRenditionsDirectory($dir) {
        $baseDir = sys_get_temp_dir() . '/binarypool_s3_tmp/';
        if (!file_exists($baseDir)) {
            mkdir($baseDir, 0755, true);
        }
        
        $tmpfile = tempnam($baseDir, 'rend');
        unlink($tmpfile);
        mkdir($tmpfile, 0700, true);
        if ($tmpfile[strlen($tmpfile)-1] !== '/') {
            $tmpfile .= '/';
        }
        return $tmpfile;
    }

    public function saveRenditions($renditions, $dir) {
        if ($dir[strlen($dir)-1] !== '/') {
            $dir .= '/';
        }

        $retval = array();
        $tmpdir = '';
        foreach ($renditions as $name => $file) {
            $remote_file = $dir . basename($file);
            $this->save($file, $remote_file);
            $retval[$name] = $remote_file;
            
            // Remove the temporary file
            if ( file_exists($file) ) {
                unlink($file);
            } else {
                $log = new api_log();
                $log->warn(
                	"storage_driver_s3::saveRenditions() - No temp file for rendition '%s' at '%s'",
                    $name,
                    $file
                    );
            }
            
            if ( empty($tmpdir) ) {
                $tmpdir = dirname($file);
            }
        }
        
        if ($tmpdir !== '') {
            // Optimistic removal. If it's not empty, this will fail.
            @rmdir($tmpdir);
            if ( file_exists($tmpdir) ) {
                $log = new api_log();
                $log->warn(
                	"storage_driver_s3::saveRenditions() - unable to remove rendition tmpdir '%s'",
                    $tmpdir
                    );
            }
        }
        
        return $retval;
    }
    
    public function rename($source, $target) {
        if ($source[strlen($source)-1] !== '/') {
            $source .= '/';
        }
        if ($target[strlen($target)-1] !== '/') {
            $target .= '/';
        }
        
        $this->removeCache($source);
        $this->removeCache($target);
        $this->flushCache($source);
        $this->flushCache($target);
        
        $s3_bucket = $this->cfg['bucket'];
        $files = $this->client->getBucket($s3_bucket, $source);
        if (is_array($files)) {
            foreach (array_keys($files) as $file) {
                $relative = str_replace($source, '', $file);
                $this->client->copyObject($s3_bucket, $file,
                                          $s3_bucket, $target . $relative);
                $this->client->deleteObject($s3_bucket, $file);
            }
        }
        
        return true;
    }
    
    public function fileExists($file) {
        if ($this->isFile($file) || $this->isDir($file)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function isFile($file) {
        if ( !array_key_exists($file, self::$isFileCache) ) {
            self::$isFileCache[$file] = $this->resolveIsFile($file);
        }
        return self::$isFileCache[$file];
    }
    
    private function resolveIsFile($file) {
        $ckey = 'isfile_' . $this->getCacheKey($file);
        
        $retval = (int)$this->cache->get($ckey);
        if ( $retval > 0 ) {
            return True;
        }
        
        // Allow for 10 failed attempts before we cache the
        // "not found" to prevent "hammering" S3 with requests
        // for something that isn't there
        if ( $retval < -10 ) {
            return False;
        }

        $file = ltrim($file, '/');
        try {
            $info = $this->client->getObjectInfo(
                $this->cfg['bucket'], $file, false);
        } catch (S3Exception $e) {
            if ($e->getCode() == 403) {
                // Requested with query string => not a file
                return False;
            }
        }
        
        if ( $info === true ) {
            $this->cache->set($ckey, 1);
            return True;
        }
        
        // decrement the fail count
        $this->cache->set($ckey, --$retval);
        return False;
        
    }
    
    public function isDir($file) {
        if ( !array_key_exists($file, self::$isDirCache) ) {
            self::$isDirCache[$file] = $this->resolveIsDir($file);
        }
        return self::$isDirCache[$file];
    }
    
    private function resolveIsDir($file) {
        $ckey = 'isdir_' . $this->getCacheKey($file);
        
        $retval = (int)$this->cache->get($ckey);
        if ( $retval > 0 ) {
            return True;
        }

        // see resolveIsFile for explanation
        if ( $retval < -10 ) {
            return False;
        }

        $dir = trim($file, '/') . '/';
        $s3_bucket = $this->cfg['bucket'];
        $files = $this->client->getBucket($s3_bucket, $dir);
        
        if (is_array($files) && count($files) > 0) {
            $this->cache->set($ckey, 1);
            return True;
        }

        $this->cache->set($ckey, --$retval);
        return False;

    }
    
    public function getFile($file) {
        $ckey = 'getfile_' . $this->getCacheKey($file);
        if ($retval = $this->cache->get($ckey)) {
            return $retval;
        }

        $file = ltrim($file, '/');
        $response = $this->client->getObject(
            $this->cfg['bucket'], $file);
        if ($response->code !== 200) {
            return null;
        } else {
            $body = $response->body;
            if ($body instanceof SimpleXMLElement) {
                $body = $body->asXML();
            }
            $this->cache->set($ckey, $body);
            return $body;
        }
    }
    
    protected function removeCache($file) {
        $ckey = 'getfile_' . $this->getCacheKey($file);
        $this->cache->del($ckey);
    }
    
    public function sendFile($file) {
        $url = $this->absolutize($file);
        $fproxy = new binarypool_fileobject($url);
        
        if ( !$fproxy->exists() ) {
            throw new binarypool_exception(115, 404, "File not found: " . $file);
        }
        
        readfile($fproxy->file);
    }
    
    public function isAbsoluteStorage() {
        return true;
    }
    
    public function listDir($dir) {
        $dir = rtrim($dir, '/') . '/';
        $s3_bucket = $this->cfg['bucket'];
        $files = $this->client->getBucket($s3_bucket, $dir);
        $retval = array();
        
        foreach ($files as $file) {
            $path = $file['name'];
            if (strrpos($path, '.link') === strlen($path)-5) {
                $path = $this->resolveSymlink($path);
            }
            if (strpos($path, 'index.xml') !== false) {
                $asset = new binarypool_asset($this, $path);
                array_push($retval, $asset->getBasePath() . 'index.xml');
            }
        }
        return $retval;
    }
    
    public function unlink($file) {
        $s3_bucket = $this->cfg['bucket'];
        $retval = $this->client->deleteObject($s3_bucket, $file);
        $this->removeCache($file);
        $this->flushCache($file);
        return $retval;
    }
    
    public function symlink($target, $link) {
        $link = $this->correctSymlinkName($link);
        
        if ($this->isFile($link)) {
            return;
        }
        
        $this->writeSymlink($target, $link);
        
    }
    
    public function relink($target, $link) {
        $link = $this->correctSymlinkName($link);
        $this->writeSymlink($target, $link);
    }
    
    public function getURLLastModified($url, $symlink, $bucket) {
        $symlink .= '.link';
        $now = $this->time;
        if (!$this->fileExists($symlink)) {
            return array('time' => 0, 'revalidate' => true, 'cache_age' => 0);
        }
        
        $contents = $this->getFile($symlink);
        $contents = json_decode($contents, true);
        
        if ($contents['link'] == '/dev/null') {
            // Dead URL
            $failed_time = $now - $contents['mtime'];
            if ($failed_time > binarypool_config::getBadUrlExpiry()) {
                $this->unlink($symlink);
                return array('time' => 0, 'revalidate' => true, 'cache_age' => $failed_time);
            }
            
            $failed_nextfetch = ($contents['mtime'] + binarypool_config::getBadUrlExpiry()) - $now;
            throw new binarypool_exception(122, 400, "File download failed $failed_time seconds ago. Re-fetching allowed in next time in $failed_nextfetch seconds: $url");
        }
        
        $cache_age = $now - $contents['mtime'];
        $revalidate = false;
        if ($cache_age > binarypool_config::getCacheRevalidate($bucket)) {
            $revalidate = true;
        }
        
        return array(
            'time' => $contents['mtime'],
            'revalidate' => $revalidate,
            'cache_age' => $cache_age); 
    }
    
    public function getAssetForLink($bucket, $symlink) {
        $symlink .= '.link';
        return str_replace('//', '/', $this->resolveSymlink($symlink) . '/index.xml');
    }
    
    /**
     * Flush the in-memory caches for this request - $isFileCache
     * and $isDirCache
     */
    public static function resetMemoryCaches() {
        self::$isFileCache = array();
        self::$isDirCache = array();
    }
    
    protected function resolveSymlink($path) {
        $contents = $this->getFile($path);
        if ($contents === null) {
            return false;
        }
        $contents = json_decode($contents, true);
        
        $path = dirname($path);
        while (strpos($contents['link'], '../') === 0) {
            $path = dirname($path);
            $contents['link'] = substr($contents['link'], 3);
        }
        $path = rtrim($path, '/') . '/';
        $path .= $contents['link'];
        return $path;
    }
    
    protected function getCacheKey($file) {
        return 'binp_' . $this->cfg['bucket'] . sha1($file);
    }
    
    protected function flushCache($file) {
        $url = $this->absolutize($file);
        binarypool_fileobject::forgetCache($url);
        $this->cache->del($this->getCacheKey($file));
        $this->cache->del('isfile_' . $this->getCacheKey($file));
        $this->cache->del('isdir_' . $this->getCacheKey($file));
        $this->resetMemoryCaches();
    }
    
    protected function correctSymlinkName($link) {
        if (strrpos($link, '.link') !== strlen($link)-5) {
            return $link . '.link';
        }
        return $link;
    }
    
    protected function writeSymlink($target, $link) {
        // Remove the HTTP cache
        $url = $this->absolutize($link);
        binarypool_fileobject::forgetCache($url);
        
        $json = json_encode(array(
            'link' => $target,
            'mtime' => $this->time,
        ));
        
        $s3_bucket = $this->cfg['bucket'];
        $this->removeCache($link);
        $this->flushCache($link);
        return $this->client->putObject(
            $json,
            $s3_bucket,
            $link,
            S3::ACL_PUBLIC_READ,
            array(),
            'application/x-symlink'
        );
        
    }
    
}
