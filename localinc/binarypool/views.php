<?php
require_once(dirname(__FILE__) . '/exception.php');
require_once(dirname(__FILE__) . '/asset.php');
require_once(dirname(__FILE__) . '/config.php');

/**
 * Handles storing of files in different views.
 * This is achieved by creating symbolic links to the corresponding
 * directories.
 *
 * The following views are maintained:
 *    - by creation date
 *
 * Dates are stored as YYYY/MM/DD.
 */
class binarypool_views {
    /**
     * Hook function for the case when a binary is created.
     * Will create all the symbolic as needed for this
     * binary.
     *
     * @param $bucket: Bucket where the asset lies in.
     * @param $asset: Relative to a valid asset file.
     * @param $metadata: e.g. URL if binary fetched from a url  
     */
    public static function created($bucket, $asset, $metadata = array()) {
        $storage = new binarypool_storage($bucket);
        $assetObj = $storage->getAssetObject($asset);
        
        self::linkCreationDate($bucket, $assetObj);
        self::linkExpirationDate($bucket, $assetObj);
        self::linkURL($bucket, $assetObj, $metadata);
    }
    
    /**
     * Hook function for the case when a binary is updated.
     * Will create all the symbolic as needed for this
     * binary and might delete old links.
     *
     * @param $bucket:      Bucket where the asset lies in.
     * @param $asset:       Relative path to a valid asset file.
     * @param $oldAssetObj: The version of the asset file before saving.
     */
    public static function updated($bucket, $asset, $oldAssetObj) {
        $storage = new binarypool_storage($bucket);
        $assetObj = $storage->getAssetObject($asset);
        
        self::linkExpirationDate($bucket, $assetObj, $oldAssetObj);
    }
    
    /**
     * Create the creation date symlink.
     *
     * @param $asset: An object of binarypool_asset.
     */
    private static function linkCreationDate($bucket, $asset) {
        $dateDir = date('Y/m/d', $asset->getCreated());
        $fullDateDir = $bucket . '/created/' . $dateDir;
        $assetDir = '../../../../' . self::getCleanedBasepath($asset);
        $symlink = $fullDateDir . '/' . $asset->getHash();
        
        self::createLink($bucket, $assetDir, $symlink);
    }
    
    /**
     * Create the expiration date symlink.
     *
     * @param $asset:    An object of binarypool_asset.
     * @param $oldasset: An object of binarypool_asset representing
     *                   the previous state. Null if $asset is a new
     *                   asset file.
     */
    private static function linkExpirationDate($bucket, $asset, $oldasset = null) {
        if (!is_null($oldasset) && $asset->getExpiry() == $oldasset->getExpiry()) {
            return;
        }
        
        // Delete old link
        if (!is_null($oldasset)) {
            $dateDir = date('Y/m/d', $oldasset->getExpiry());
            $fullDateDir = $bucket . '/expiry/' . $dateDir;
            $symlink = $fullDateDir . '/' . $asset->getHash();
            $storage = new binarypool_storage($bucket);
            $storage->unlink($symlink);
        }
        
        $dateDir = date('Y/m/d', $asset->getExpiry());
        $fullDateDir = $bucket . '/expiry/' . $dateDir;
        
        $assetDir = '../../../../' . self::getCleanedBasepath($asset);
        $symlink = $fullDateDir . '/' . $asset->getHash();
        self::createLink($bucket, $assetDir, $symlink);
    }
    
    /**
     * Create the URL view symlink for files which were downloaded 
     *
     * @param $asset: An object of binarypool_asset.
     */
    private static function linkURL($bucket, $asset, $metadata) {
        if ( empty($metadata['URL']) ) {
            return;
        }
        
        $assetDir = '../../' . self::getCleanedBasepath($asset);
        $symlink = self::getDownloadedViewPath($bucket, $metadata['URL']);
        
        $lastmodified = api_command_create::lastModified($bucket, $metadata['URL']);
        
        $refresh = False;
        if ( $lastmodified['cache_age'] > binarypool_config::getCacheRevalidate($bucket) ) {
            $refresh = True;
        }
        
        self::createLink($bucket, $assetDir, $symlink, $refresh);
    }
    
    /**
     * A URL which we couldn't access gets flagged as "bad" by creating
     * a symlink in the 'downloaded' view pointing to '/dev/null'
     *
     * @see api_command_create::getURLLastModified
     * @param String $bucket
     * @param String $url
     */
    public static function flagBadUrl($bucket, $url) {
        $symlink = self::getDownloadedViewPath($bucket, $url);
        self::createLink($bucket, '/dev/null', $symlink);
    }
    
    /**
     * Returns the expected path on the filesystem inside the
     * 'downloaded' view for a given bucket and URL to a remote
     * binary 
     *
     * @param String $bucket
     * @param String $url
     * @return String filesystem path
     */
    public static function getDownloadedViewPath($bucket, $url) {
        if ( !strpos($bucket, '/') === 0 ) {
            $bucket = '/'.$bucket;
        }
        
        $urlhash = sha1($url);
        
        return sprintf( "%s/downloaded/%s/%s",
                        $bucket,
                        substr($urlhash, 0, 2),
                        $urlhash
                      );
    }
    
    /**
     * Returns a basepath without bucket and trailing slash
     * for the given asset.
     */
    private static function getCleanedBasepath($asset) {
        $basepath = $asset->getBasePath();
        $basepath = substr($basepath, strpos($basepath, '/')+1);
        if (substr($basepath, strlen($basepath)-1) == '/') {
            $basepath = substr($basepath, 0, strlen($basepath)-1);
        }
        return $basepath;
    }
    
    /**
     * Creates a symbolic link, also creating all parent directories
     * if necessary.
     */
    private static function createLink($bucket, $target, $link, $refresh = false) {
        $storage = new binarypool_storage($bucket);
        $storage->symlink($target, $link, $refresh);
    }
}
