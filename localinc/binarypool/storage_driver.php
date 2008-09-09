<?php
/**
 * Base class for all storage drivers.
 */
abstract class binarypool_storage_driver {
    /**
     * Return the absolute path to the given file or path.
     * The file does not have to exist yet.
     *
     * This can return either a local file system URL or a
     * HTTP url.
     *
     * @param $file string: File name relative to the root of the
     *        represented file system. (Root depends on the definition
     *        of the driver).
     */
    public abstract function absolutize($file);

    /**
     * Saves a file into the target file system.
     *
     * @param $local_file string: Absolute path to the local file to be
     *        stored in the target file system.
     * @param $remote_file string: Path relative to the root of the
     *        target file system to place the file to.
     */
    public abstract function save($local_file, $remote_file);
    
    /**
     * Return a local file directory directory where renditions can be
     * saved to. If the storage represents a local file system, then
     * the final path can already be returned.
     *
     * For more complex drivers a temporary directory is to be returned.
     * All renditions saved to this folder will later be passed to the
     * saveRenditions() method.
     *
     * This directory *MUST NOT* be removed outside of the driver class.
     *
     * @param $dir string: Directory relative to the root of the
     *        represented file system which was allocated for the
     *        renditions which are to be generated.
     */
    public abstract function getRenditionsDirectory($dir);

    /**
     * Save some renditions which have been calculated to their final
     * storage destination.
     *
     * As a side-effect this function may remove the directory previously
     * returned by getRenditionsDirectory(). So you can not rely on the
     * renditions still being around after calling this function.
     *
     * @param $renditions array: List of absolute file names. These files
     *        must be located inside the path previously returned by
     *        getRenditionsDirectory().
     * @param $dir string: Directory relative to the root of the
     *        represented file system which was allocated for the
     *        renditions which are to be generated.
     * @return array: The list of files with all paths corrected to be
     *         relative to the root of the represented file system.
     */
    public abstract function saveRenditions($renditions, $dir);
    
    /**
     * Renames a directory, moving it to a new folder. The target folder
     * and it's parent folders do not necessarily exist yet.
     * Additionally the source folder may already be gone, a case
     * which is ignored silently.
     *
     * @param $source string: Relative path of the folder to rename.
     * @param $target string: Relative path of where to rename the folder to.
     */
    public abstract function rename($source, $target);
    
    /**
     * Returns true if the given file exists
     *
     * @param $file string: Relative path of the file to check.
     * @return boolean: True if the file exists, false otherwise.
     */
    public abstract function fileExists($file);
    
    /**
     * Returns true if the given file exists and is a file.
     *
     * @param $file string: Relative path of the file to check.
     * @return boolean: True if the file exists and is a file.
     */
    public abstract function isFile($file);
    
    /**
     * Returns true if the given file exists and is a directory.
     *
     * @param $file string: Relative path of the file to check.
     * @return boolean: True if the file exists and is a directory.
     */
    public abstract function isDir($file);
    
    /**
     * Returns the contents of the given file.
     *
     * @param $file string: Relative path of the file to return.
     * @return string: Contents of the file.
     */
    public abstract function getFile($file);
    
    /**
     * Sends the contents of the given file to the browser.
     *
     * @param $file string: Relative path of the file to send.
     */
    public abstract function sendFile($file);
    
    /**
     * Determines if the storage requires absolute URLs in the
     * asset files. If true, the asset file will contain URLs
     * instead of just relative path references to the renditions.
     * 
     * @return boolean: True if absolute storage is turned on.
     */
    public abstract function isAbsoluteStorage();
    
    /**
     * Return a list of asset names which are found in the given
     * directory.
     *
     * @param $dir: Name of the directory to look in. All index.xml
     *              documents found in the folders inside that directory
     *              nodes are returned.
     * @return array: Array of path names.
     */
    public abstract function listDir($dir);
    
    /**
     * Remove a file from the storage.
     *
     * @param $file string: Relative path of the file to remove.
     */
    public abstract function unlink($file);
    
    /**
     * Create a symlink.
     *
     * @param $target string: Path of the file to link to, relative to
     *                        the link.
     * @param $link string:   Path of the symlink to create, relative to
     *                        the storage root.
     * @param $refresh bool:  True if the link should be touched when
     *                        it already exists.
     */
    public abstract function symlink($target, $link, $refresh = false);
    
    /**
     * Checks if the given URL should be downloaded again.
     *
     * @param $url: URL to verify.
     * @param $symlink: Symlink to use for verification as returned by
     *                  binarypool_views::getDownloadedViewPath()
     * @retval hash: Associative array with the following values:
     *    - time: Last time the URL was downloaded.
     *    - revalidate: True if the URL should be downloaded again,
     *                  false otherwise.
     *    - cache_age: Seconds since the URL was last downloaded.
     */
    public abstract function getURLLastModified($url, $symlink);
    
    /**
     * Returns the asset ID where the symlink points to.
     *
     * @param $bucket: Name of the bucket the symlink is in.
     * @param $symlink: Path relative to the storage root.
     */
    public abstract function getAssetForLink($bucket, $symlink);
}
