<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/render_image.php');
require_once(dirname(__FILE__).'/storage.php');

class binarypool_render {
    private static $MIME_MOVIES = array(
        'application/x-shockwave-flash',
    );
    
    
    /**
     * Calculates the renditions for the given file.
     *
     * @param $type: Type - the renderer is chosen based on this type.
     * @param $bucket: Bucket and type define the renditions which have
     *                 to be generated.
     * @param $original: Absolute path to the original file.
     * @param $outputPath: Directory where the renditions are written to.
     * @param $exclude: Renditions which don't have to be calculated.
     * @return Associative array of hashes. The key is the rendition name,
     *         the path is the relative path of the file.
     */
    public static function render($type, $bucket, $original, $outputPath, $exclude = array()) {
        $buckets = binarypool_config::getBuckets();
        $renderingClass = "binarypool_render_" . strtolower($type);
        
        $renditions = array();
        if (! isset($buckets[$bucket]['renditions'][$type])) {
            return $renditions;
        }
        
        foreach ($buckets[$bucket]['renditions'][$type] as $rendition => $renditionConfig) {
            if (in_array($rendition, $exclude)) {
                continue;
            }
            
            $renditionFile = $rendition;
            $absoluteRendition = $outputPath . $renditionFile;
            
            // Render image. Return value of rendering is the absolute path to the
            // rendition including file extensions.
            $absoluteRendition = call_user_func($renderingClass . '::resize',
                $original, $absoluteRendition,
                $renditionConfig['width'], $renditionConfig['height']
            );
            
            if (!file_exists($absoluteRendition)) {
                throw new binarypool_exception(106, 500, "Could not create rendition: $rendition");
            }
            $renditions[$rendition] = $absoluteRendition;
        }
        
        return $renditions;
    }
    
    /**
     * Returns the extension of the given file.
     */
    public static function getFileExtension($file) {
        $filename = basename($file);
        $parts = explode('.', $filename);
        if (count($parts) > 1) {
            return $parts[count($parts)-1];
        } else {
            return '';
        }
    }
    
    /**
     * Returns the object type for the given MIME type.
     *
     * Default return value is IMAGE.
     */
    public static function getType($mime) {
        if (in_array($mime, self::$MIME_MOVIES)) {
            return 'MOVIE';
        } else {
            return 'IMAGE';
        }
    }
}
?>
