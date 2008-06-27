<?php
require_once(dirname(__FILE__).'/config.php');

class binarypool_validate_image {
    /**
     * Validates image files.
     * $config must be an associative array with the following keys:
     *    - mime: Array of MIME types to allow (whitelist).
     */
    public static function validate($source, $config = array()) {
        if (is_array($config)) {
            if (isset($config['mime']) && is_array($config['mime']) && sizeof($config['mime']) > 0) {
                $mime = binarypool_mime::getMimeType($source);
                if (! in_array($mime, $config['mime'])) {
                    throw new binarypool_exception(119, 400, "Invalid MIME type for image. Allowed types: " .
                        implode(', ', $config['mime']));
                    return false;
                }
            }
        }
        
        return true;
    }
}
