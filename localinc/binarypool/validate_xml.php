<?php
require_once(dirname(__FILE__).'/config.php');

class binarypool_validate_xml {
    /**
     * Validates XML files.
     * If present, $config must be the path to a schema file which to
     * validate against.
     */
    public static function validate($source, $config = null) {
        // Without shema first
        self::lint($source);
        
        if (! is_null($config)) {
            if (!file_exists($config)) {
                error_log("XSD file does not exist: $config");
                return true;
            }
            
            // Validate again, with the schema
            self::lint($source, $config);
        }
        
        return true;
    }
    
    protected static function lint($source, $xsd = null) {
        $cmd = binarypool_config::getUtilityPath('xmllint');
        $cmd .= ' --nowarning --nonet --noout';
        if (! is_null($xsd)) {
            $cmd .= ' --schema ' . escapeshellarg($xsd);
        }
        
        $cmd = $cmd . ' ' . escapeshellarg($source) . ' >/dev/null 2>/dev/null';
        system($cmd, $retval);
        
        if ($retval != 0) {
            if (is_null($xsd)) {
                throw new binarypool_exception(117, 400, "XML document is not well-formed.");
            } else {
                throw new binarypool_exception(118, 400, "XML document is not valid.");
            }
        }
        
        return true;
    }
}
