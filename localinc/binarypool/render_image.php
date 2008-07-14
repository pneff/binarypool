<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/render.php');

class binarypool_render_image {
    /**
     * Resize an image file into an output file.
     *
     * @param $source: Absolute file path to the original file.
     * @param $target: Absolute file path without extension to the new file.
     * @param $maxWidth: Maximum width of the new image. No resizing
     *                   is done if this is empty. Can be used without
     *                   specifying maxHeight.
     * @param $maxHeight: Maximum height of the new image. Must be
     *                    used together with $maxWidth.
     * @return: The absolute file path to the rendition including an
     *          appropriate extension.
     */
    public static function resize($source, $target, $maxWidth, $maxHeight) {
        $mime = binarypool_mime::getMimeType($source);
        $target = self::getTargetFile($source, $mime, $target);
        self::convert($source, $target, $mime, $maxWidth, $maxHeight);
        return $target;
    }
    
    /**
     * Convert a picture to a different format and/or resize it.
     *
     * @param $from: File path to the original file.
     * @param $to: File path where the new file should be saved.
     * @param $mime: MIME type of the original image.
     * @param $maxWidth: Maximum width of the new image. No resizing
     *                   is done if this is empty. Can be used without
     *                   specifying maxHeight.
     * @param $maxHeight: Maximum height of the new image. Must be
     *                    used together with $maxWidth.
     */
    protected static function convert($from, $to, $mime, $maxWidth = '', $maxHeight = '') {
        $cmd = binarypool_config::getUtilityPath('convert');
        
        # Make sure all output images are in RGB. This handles incoming CMYK
        # images which some browsers can't display.
        $cmd .= ' -colorspace RGB';
        
        switch ($mime) {
           case 'image/pdf':
           case 'application/pdf':
               $cmd .= ' -trim';
               break;

           default:
               $cmd .= ' -flatten';
               break;
        }

        if ($maxWidth != '') {
            $scale = intval($maxWidth);
            if ($maxHeight != '') {
                $scale .= 'x' . intval($maxHeight);
                # Don't enlarge if the size is already smaller than resized version
                $scale .= '>';
            }
            $cmd .= ' -resize ' . escapeshellarg($scale) . '';
        }
        
        if ($mime == 'image/jpeg') {
            # Sharpen image
            $cmd .= ' -unsharp 0.5x1';
        }
        
        $cmd = $cmd . ' ' . escapeshellarg($from) . ' ' . escapeshellarg($to);
        
        $log = new api_log();
        $log->debug("Resizing image using command: $cmd");
        shell_exec($cmd);
    }
    
    /**
     * Determines the extension for the target file based on the input file..
     */
    protected static function getTargetFile($source, $mime, $target) {
        $origExtension = binarypool_render::getFileExtension($source);
        $allowedExtensions = array('gif', 'png', 'jpg');
        $newExtension = $origExtension;
        
        switch ($mime) {
            case 'image/bmp':
            case 'image/tiff':
            case 'application/octet-stream':
            case 'image/jpeg':
                $newExtension = 'jpg';
                break;
            case 'application/pdf':
            case 'image/pdf':
            case 'image/png':
                $newExtension = 'png';
                break;
            case 'image/gif':
                $newExtension = 'gif';
                break;
        }
        if ($newExtension == '' || !in_array($newExtension, $allowedExtensions)) {
            $newExtension = 'jpg';
        }
        
        return $target . '.' . $newExtension;
    }
}
?>
