<?php
class binarypool_mime {
    /**
     * Returns the MIME type of the given file.
     *
     * @param $file: A file path or URL.
     */
    public static function getMimeType($file) {
        $strategies = array(
            'Finfo',
            'CmdLineFile',
            'GetImageSize',
        );
        
        foreach ($strategies as $strategy) {
            $mime = call_user_func('binarypool_mime::getMimeTypeWith' . $strategy, $file);
            if (!is_null($mime)) {
                return $mime;
            }
        }
        
        return null;
    }
    
    /**
     * Uses finfo if available to get the MIME type.
     */
    protected static function getMimeTypeWithFinfo($file) {
        if (! function_exists('finfo_open')) {
            return null;
        }
        
        $res = finfo_open(FILEINFO_MIME);
        $mime = finfo_file($res, $file);
        finfo_close($res);
        
        // fix for a bug - EPS and PDF are detected as audio/x-mod sometimes
        $tmpname = escapeshellarg($file);
        if ($mime == 'audio/x-mod') {
            $mime = self::getMimeTypeWithCmdLineFile($file);
        } else if (strpos($mime, "application/") === 0) {
            if (in_array($mime, array('image/eps', 'image/pdf', 'application/pdf'))) {
                // check if it's an EPS/PDF, I only found out how to do it on the commandline...
                $alternativeMime = self::getMimeTypeWithCmdLineFile($file);
                $mime = $alternativeMime;
            }
        }
        
        return $mime;
    }
    
    /**
     * Uses `file' on the command line to get the MIME type.
     */
    protected static function getMimeTypeWithCmdLineFile($file) {
        $cmd = binarypool_config::getUtilityPath('file');
        $mime = trim(shell_exec("file -ib " . escapeshellarg($file)));
        
        if ($mime == '' || $mime == 'regular file') {
            return null;
        } else {
            return $mime;
        }
    }
    
    /**
     * Uses the getimagesize function to get the MIME type.
     */
    protected static function getMimeTypeWithGetImageSize($file) {
        $info = getimagesize($file);
        return $info['mime'];
    }
}
?>
