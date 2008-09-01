<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/render.php');
require_once(dirname(__FILE__).'/render_base.php');

class binarypool_render_pdf extends binarypool_render_base {
    /**
     * Converts a PDF or EPS input file to JPG or PNG.
     *
     * Config must provide the following keys:
     * - format: jpg or png.
     */
    public static function render($source, $target, $assetFile, $config) {
        $format = $config['format'];
        $target = $target . '.' . $format;
        self::convert($source, $target, $format);
        return $target;
    }
    
    /**
     * Convert a PDF/EPS to JPG/PNG.
     *
     * @param $from:   File path to the original file.
     * @param $to:     File path where the new file should be saved.
     * @param $format: Format to convert to.
     */
    protected static function convert($from, $to, $format) {
        $cmd = binarypool_config::getUtilityPath('pdfconverter');
        $cmd .= ' -f ' . $format;
        $cmd .= ' ' . escapeshellarg($from) . ' ' . escapeshellarg($to);
        
        $log = new api_log();
        $log->debug("Rendering PDF with command: $cmd");
        $out = shell_exec($cmd);
        $log->debug("Command output was: $out");
    }
}
