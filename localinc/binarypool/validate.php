<?php
require_once(dirname(__FILE__).'/validate_xml.php');

class binarypool_validate {
    /**
     * Validate original uploaded files and throw exceptions if they are
     * invalid.
     *
     * @param $type: Type - the validation method is chosen based on this type.
     * @param $bucket: Bucket and type define the validations that apply.
     * @param $original: Absolute path to the original file.
     * @return Boolean
     */
    public static function validate($type, $bucket, $original) {
        $validationClass = "binarypool_validate_" . strtolower($type);
        if (! class_exists($validationClass)) {
            return true;
        }
        
        $config = null;
        $buckets = binarypool_config::getBuckets();
        if (isset($buckets[$bucket]['validations']) && isset($buckets[$bucket]['validations'][$type])) {
            $config = $buckets[$bucket]['validations'][$type];
        }
        
        return call_user_func($validationClass . '::validate', $original, $config);
    }
}
