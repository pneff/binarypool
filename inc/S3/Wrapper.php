<?php
require_once(dirname(__FILE__) . '/../S3.php');

/**
 * Small wrapper over the S3 class so I don't have to call the S3
 * methods statically.
 *
 * This way we can actually generate a mock object for the S3 class.
 */
class S3_Wrapper {
    public function __construct($accessKey = null, $secretKey = null) {
        S3::setAuth($accessKey, $secretKey);
    }
    
    public function putObject($input, $bucket, $uri, $acl = self::ACL_PRIVATE, $metaHeaders = array(), $contentType = null) {
        return S3::putObject($input, $bucket, $uri, $acl, $metaHeaders, $contentType);
    }
    
    public function putObjectFile($file, $bucket, $uri, $acl = S3::ACL_PRIVATE, $metaHeaders = array(), $contentType = null) {
        return S3::putObjectFile($file, $bucket, $uri, $acl, $metaHeaders, $contentType);
    }

    public function getObjectInfo($bucket = '', $uri = '', $returnInfo = true) {
        return S3::getObjectInfo($bucket, $uri, $returnInfo);
    }

    public function copyObject($srcBucket, $srcUri, $bucket, $uri) {
        return S3::copyObject($srcBucket, $srcUri, $bucket, $uri);
    }

    public function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null) {
        return S3::getBucket($bucket, $prefix, $marker, $maxKeys);
    }

    public function deleteObject($bucket = '', $uri = '') {
        return S3::deleteObject($bucket, $uri);
    }
    
    public function getObject($bucket = '', $uri = '', $saveTo = false) {
        return S3::getObject($bucket, $uri, $saveTo);
    }
}
