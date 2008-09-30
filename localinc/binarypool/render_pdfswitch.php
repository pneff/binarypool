<?php
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/render.php');
require_once(dirname(__FILE__).'/render_base.php');
require_once(dirname(__FILE__).'/render_eps.php');
require_once(dirname(__FILE__).'/render_pdf.php');

/**
 * Uses render_pdf for PDFs and render_eps for EPS.
 */
class binarypool_render_pdfswitch extends binarypool_render_base {
    public static function render($source, $target, $assetFile, $config) {
        $finfo = binarypool_fileinfo::getFileinfo($source);
        if ($finfo['mime'] === 'application/pdf') {
            return binarypool_render_pdf::render($source, $target,
                $assetFile, $config);
        } else {
            return binarypool_render_eps::render($source, $target,
                $assetFile, $config);
        }
    }
}
