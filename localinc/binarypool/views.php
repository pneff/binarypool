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
        $assetObj = new binarypool_asset(binarypool_config::getRoot() . $asset);
        
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
     * @param $asset:       Relative to a valid asset file.
     * @param $oldAssetObj: The version of the asset file before saving.
     */
    public static function updated($bucket, $asset, $oldAssetObj) {
        $assetObj = new binarypool_asset(binarypool_config::getRoot() . $asset);
        
        self::linkExpirationDate($bucket, $assetObj, $oldAssetObj);
    }
    
    /**
     * Create the creation date symlink.
     *
     * @param $asset: An object of binarypool_asset.
     */
    private static function linkCreationDate($bucket, $asset) {
        $dateDir = date('Y/m/d', $asset->getCreated());
        $absDateDir = binarypool_config::getRoot() . $bucket . '/created/' . $dateDir;
        
        $assetDir = '../../../../' . self::getCleanedBasepath($asset);
        $symlink = $absDateDir . '/' . $asset->getHash();
        self::createLink($assetDir, $symlink);
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
            $absDateDir = binarypool_config::getRoot() . $bucket . '/expiry/' . $dateDir;
            $symlink = $absDateDir . '/' . $asset->getHash();
            unlink($symlink);
        }
        
        $dateDir = date('Y/m/d', $asset->getExpiry());
        $absDateDir = binarypool_config::getRoot() . $bucket . '/expiry/' . $dateDir;
        
        $assetDir = '../../../../' . self::getCleanedBasepath($asset);
        $symlink = $absDateDir . '/' . $asset->getHash();
        self::createLink($assetDir, $symlink);
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
        if ( $lastmodified['cache_age'] > binarypool_config::getCacheRevalidate() ) {
            $refresh = True;
        }
                
        self::createLink($assetDir, $symlink, $refresh);
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
        self::createLink('/dev/null', $symlink);
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
        
        return sprintf( "%s%s/downloaded/%s/%s",
                        binarypool_config::getRoot(),
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
    private static function createLink($target, $link, $refresh = false) {
        if (! file_exists(dirname($link))) {
            mkdir(dirname($link), 0755, true);
        }

        if (! file_exists($link)) {
            
            symlink($target, $link);
            
        } else if ( $refresh ) {
            
            // "touch" the symlink - use specific to downloaded view where
            // we only want to revalidate older cache entries
            $tmplink = sprintf("/tmp/%s%s", sha1($link), microtime(True));
            symlink($target, $tmplink);
            rename($tmplink, $link);
            
        }
    }
}
?>
