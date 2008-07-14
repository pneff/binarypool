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
     * Fixes the extension of the passed in file name according to the
     * file's MIME type.
     * @param $file string: Absolute file path.
     * @return string: File name (no path) with fixed extension
     */
    public static function fixExtension($file, $basename) {
        $info = pathinfo($file);
        if (!empty($basename)) {
            $info = pathinfo($basename);
        }
        $base = $info['filename'];
        $ext = isset($info['extension']) ? $info['extension'] : '';
        
        $mime = self::getMimeType($file);
        
        switch ($mime) {
            case 'image/bmp':
                $ext = 'bmp';
                break;
            case 'image/tiff':
                $ext = 'tif';
                break;
            case 'image/jpeg':
                $ext = 'jpg';
                break;
            case 'image/gif':
                $ext = 'gif';
                break;
            case 'image/png':
                $ext = 'png';
                break;
            case 'application/pdf':
            case 'image/pdf':
                $ext = 'pdf';
                break;
        }
        
        if ($ext === '') {
            return $base;
        } else {
            return $base . '.' . $ext;
        }
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
