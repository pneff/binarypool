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
}
?>
