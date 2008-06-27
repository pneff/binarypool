<?php
require_once(dirname(__FILE__) . '/storage_driver.php');
require_once(dirname(__FILE__) . '/../../inc/S3/Wrapper.php');

/**
 * A storage implementation to save files into Amazon S3.
 */
class binarypool_storage_driver_s3 extends binarypool_storage_driver {
    public function __construct($cfg, $client = null) {
        $this->cfg = $cfg;
        
        if (is_null($client)) {
            $this->client = new S3_Wrapper($cfg['access_id'], $cfg['secret_key']);
        } else {
            $this->client = $client;
        }
    }

    public function absolutize($file) {
        return $this->cfg['base_url'] . $file;
    }

    public function save($local_file, $remote_file) {
        // Cache the fileinfo
        binarypool_fileinfo::setCache($this->absolutize($remote_file),
            binarypool_fileinfo::getFileinfo($local_file));

        $info = $this->client->getObjectInfo(
            $this->cfg['bucket'],
            $remote_file,
            false
        );

        if ($info === false) {
            return $this->client->putObjectFile(
                $local_file,
                $this->cfg['bucket'],
                $remote_file,
                S3::ACL_PUBLIC_READ,
                array(),
                binarypool_mime::getMimeType($local_file)
            );
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
        foreach ($renditions as $name => $file) {
            $remote_file = $dir . basename($file);
            $this->save($file, $remote_file);
            $retval[$name] = $remote_file;
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
        
        return true;
    }
}
?>
