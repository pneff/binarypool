<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/render.php');
require_once(dirname(__FILE__).'/render_base.php');

/**
 * EPS conversion using ImageMagick.
 */
class binarypool_render_eps extends binarypool_render_base {
    public static function render($source, $target, $assetFile, $config) {
        $format = $config['format'];
        $target = $target . '.' . $format;
        self::convert($source, $target);
        return $target;
    }
    
    protected static function convert($from, $to) {
        $cmd = binarypool_config::getUtilityPath('convert');
        $cmd .= ' -density 288 -resize 220 +antialias';
        
        # Make sure all output images are in RGB. This handles incoming CMYK
        # images which some browsers can't display.
        $cmd .= ' -colorspace RGB -trim ';
        $cmd .= escapeshellarg($from) . ' ' . escapeshellarg($to);
        
        $log = new api_log();
        $log->debug("Converting PDF/EPS image using command: $cmd");
        shell_exec($cmd);
    }
}
