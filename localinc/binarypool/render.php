<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/render_image.php');
require_once(dirname(__FILE__).'/render_queue.php');
require_once(dirname(__FILE__).'/render_pdf.php');
require_once(dirname(__FILE__).'/render_eps.php');
require_once(dirname(__FILE__).'/render_pdfswitch.php');
require_once(dirname(__FILE__).'/storage.php');
require_once(dirname(__FILE__).'/mime.php');

class binarypool_render {
    private static $MIME_MOVIES = array(
        'application/x-shockwave-flash',
    );
    private static $MIME_XML = array(
        'text/xml',
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
     * @param $assetFile: Path to the asset file. Needed for asynchronous
     *                    rendition processes.
     * @return Associative array of hashes. The key is the rendition name,
     *         the path is the relative path of the file.
     */
    public static function render($type, $bucket, $original,
                $outputPath, $exclude = array(), $assetFile = null) {
        // Get configured renditions for this type
        $buckets = binarypool_config::getBuckets();
        $renditions = array();
        if (! isset($buckets[$bucket]['renditions'][$type])) {
            return $renditions;
        }
        
        // Information about the original file
        $mime = binarypool_mime::getMimeType($original);
        
        // Loops through the renditions configuration and generates the
        // renditions as specified.
        // 
        // The following keys of each rendition config are considered:
        //    - _sources: Ordered array of rendition names that should be
        //                used as input for this rendition. If not present
        //                of empty elements mean that the original
        //                binary is used.
        //    - _class:   The render_base subclass to use. The class is
        //                prefixed with 'binarypool_render_' to get the
        //                definitive PHP class name.
        //    - _mimes:   MIME type of the original binary for which this
        //                rendition is calculated.
        //
        // This three keys are removed from the array and the modified
        // array is then passed to the render class as configuration.
        //
        // The list of renditions needs to be in the correct dependency
        // order. If rendition `B' uses rendition `A' as a source, then
        // rendition `A' must come before `B'.
        
        foreach ($buckets[$bucket]['renditions'][$type] as $rendition => $renditionConfig) {
            // Renditions that the user uploaded are not calculated
            if (in_array($rendition, $exclude)) {
                continue;
            }
            
            // Rendition to be calculated?
            if (isset($renditionConfig['_mimes'])) {
                $mimesCfg = $renditionConfig['_mimes'];
                if (!is_array($mimesCfg)) {
                    $mimesCfg = array($mimesCfg);
                }
                if (!in_array($mime, $mimesCfg)) {
                    continue;
                }
                unset($renditionConfig['_mimes']);
            }
            
            // Get correct source file name
            $sourceFile = null;
            if (isset($renditionConfig['_sources'])) {
                foreach ($renditionConfig['_sources'] as $sourceRendition) {
                    if (isset($renditions[$sourceRendition])) {
                        $sourceFile = $renditions[$sourceRendition];
                        break;
                    } else if ($sourceRendition === '') {
                        $sourceFile = $original;
                        break;
                    }
                }
                unset($renditionConfig['_sources']);
            } else {
                $sourceFile = $original;
            }
            if (is_null($sourceFile)) {
                throw new binarypool_exception(106, 500, "Missing source rendition for rendition $rendition");
            }
            
            $renditionConfig['_bucket'] = $bucket;
            
            // Get correct class name
            $renditionType = strtolower($type);
            if (isset($renditionConfig['_class'])) {
                $renditionType = $renditionConfig['_class'];
                unset($renditionConfig['_class']);
            }
            $renderingClass = "binarypool_render_" . $renditionType;
            
            // Filenames
            $renditionFile = $rendition;
            $absoluteRendition = $outputPath . $renditionFile;
            
            // Render image. Return value of rendering is the absolute path to the
            // rendition including file extensions.
            $absoluteRendition = call_user_func($renderingClass . '::render',
                $sourceFile, $absoluteRendition, $assetFile, $renditionConfig);
            if (!is_null($absoluteRendition)) {
                if (!file_exists($absoluteRendition)) {
                    throw new binarypool_exception(106, 500, "Could not create rendition: $rendition");
                }
                $renditions[$rendition] = $absoluteRendition;
            }
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
        } else if (in_array($mime, self::$MIME_XML)) {
            return 'XML';
        } else {
            return 'IMAGE';
        }
    }
}
