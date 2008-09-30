<?php
require_once(dirname(__FILE__) . '/storage_driver.php');
require_once(dirname(__FILE__) . '/../../inc/S3/Wrapper.php');

/**
 * A storage implementation to save files into Amazon S3.
 */
class binarypool_storage_driver_s3 extends binarypool_storage_driver {
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

        $info = $this->client->getObjectInfo(
            $this->cfg['bucket'],
            $remote_file,
            false
        );

        if ($info === false) {
            $retval = $this->client->putObjectFile(
                $local_file,
                $this->cfg['bucket'],
                $remote_file,
                S3::ACL_PUBLIC_READ,
                array(),
                binarypool_mime::getMimeType($local_file)
            );
            $this->flushCache($remote_file);
            if ($retval === false) {
                throw new binarypool_exception(105, 500, "Could not copy file to its final destination on S3: $remote_file");
            }
            return $retval;
        } else {
            return true;
        }
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
            unlink($file);
            $tmpdir = dirname($file);
        }
        
        if ($tmpdir !== '') {
            // Optimistic removal. If it's not empty, this will fail.
            @rmdir($tmpdir);
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
        $this->flushCache($source);
        $this->flushCache($target);
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
        $ckey = 'isfile_' . $this->getCacheKey($file);
        if ($retval = $this->cache->get($ckey)) {
            return $retval;
        }

        $file = ltrim($file, '/');
        $info = $this->client->getObjectInfo(
            $this->cfg['bucket'], $file, false);
        $retval = ($info !== false);
        $this->cache->set($ckey, $retval);
        return $retval;
    }
    
    public function isDir($file) {
        $ckey = 'isdir_' . $this->getCacheKey($file);
        if ($retval = $this->cache->get($ckey)) {
            return $retval;
        }

        $dir = trim($file, '/') . '/';
        $s3_bucket = $this->cfg['bucket'];
        $files = $this->client->getBucket($s3_bucket, $dir);
        if (is_array($files) && count($files) > 0) {
            $this->cache->set($ckey, true);
            return true;
        } else {
            $this->cache->set($ckey, false);
            return false;
        }
    }
    
    public function getFile($file) {
        $file = ltrim($file, '/');
        $response = $this->client->getObject(
            $this->cfg['bucket'], $file);
        
        if ($response->code !== 200) {
            return null;
        } else {
            $body = $response->body;
            if ($body instanceof SimpleXMLElement) {
                return $body->asXML();
            } else {
                return $body;
            }
        }
    }
    
    public function sendFile($file) {
        $url = $this->absolutize($file);
        $fproxy = new binarypool_fileobject($url);
        if (!is_null($fproxy->file)) {
            readfile($fproxy->file);
        } else {
            throw new binarypool_exception(115, 404, "File not found: " . $file);
        }
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
        $this->flushCache($file);
        return $retval;
    }
    
    public function symlink($target, $link, $refresh = false) {
        // Remove the HTTP cache
        $url = $this->absolutize($link);
        binarypool_fileobject::forgetCache($url);

        if (strrpos($link, '.link') !== strlen($link)-5) {
            $link .= '.link';
        }
        
        $json = json_encode(array(
            'link' => $target,
            'mtime' => $this->time,
        ));
        
        $s3_bucket = $this->cfg['bucket'];
        return $this->client->putObject(
            $json,
            $s3_bucket,
            $link,
            S3::ACL_PUBLIC_READ,
            array(),
            'application/x-symlink'
        );
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
        return 'binp_' . preg_replace('/[^a-zA-Z0-9]+/', '_', trim($file, '/'));
    }
    
    protected function flushCache($file) {
        $url = $this->absolutize($file);
        binarypool_fileobject::forgetCache($url);
        $this->cache->del($this->getCacheKey($file));
        $this->cache->del('isfile_' . $this->getCacheKey($file));
        $this->cache->del('isdir_' . $this->getCacheKey($file));
    }
}
