<?php
class binarypool_mime {
    /**
     * Returns the MIME type of the given file.
     *
     * @param $file: A file path or URL.
     */
    public static function getMimeType($file) {
        $fproxy = new binarypool_fileobject($file);
        if (is_null($fproxy->file)) {
            return null;
        }
        
        $strategies = array(
            'Hardcoded',
            'Finfo',
            'CmdLineFileCustom',
            'CmdLineFile',
            'GetImageSize',
        );
        
        foreach ($strategies as $strategy) {
            $mime = call_user_func('binarypool_mime::getMimeTypeWith' . $strategy, $fproxy->file);
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
            case 'application/x-shockwave-flash':
                $ext = 'swf';
                break;
            case 'video/x-flv':
                $ext = 'flv';
                break;
        }
        
        if ($ext === '') {
            return $base;
        } else {
            return $base . '.' . $ext;
        }
    }
    
    /**
     * Returns the size of the image.
     */
    public function getImageSize($file, $mime = null, $type = null) {
        if (is_null($mime)) {
            $mime = self::getMimeType($file);
        }
        if (is_null($type)) {
            $type = binarypool_render::getType($mime);
        }
        
        if ($mime == 'application/pdf' or $mime == 'image/eps'
                    or $mime == 'application/postscript') {
            // Try to get size from external pdfconverter utility
            $cmd = binarypool_config::getUtilityPath('pdfconverter');
            if (!is_null($cmd)) {
                $cmd .= ' -s ';
                $cmd .= ' ' . escapeshellarg($file);
                $out = shell_exec($cmd);
                $out = explode("\n", $out);
                if (strpos($out[0], 'width:') === 0 && strpos($out[1], 'height:') === 0) {
                    return array(
                        'unit' => 'mm',
                        'width' => intval(substr($out[0], 6)),
                        'height' => intval(substr($out[1], 7)),
                    );
                }
            }
        }
        
        if ($type == 'IMAGE' || $type == 'MOVIE') {
            $size = getimagesize($file);
            return array(
                'width' => intval($size[0]),
                'height' => intval($size[1]),
                'unit' => 'px',
            );
        } else {
            return array('width' => null, 'height' => null, 'unit' => null);
        }
    }
    
    /**
     * Some hardcoded detections.
     */
    protected static function getMimeTypeWithHardcoded($file) {
        $f = fopen($file, "rb");
        $part = fread($f, 3);
        if ($part === 'FLV') {
            return 'video/x-flv';
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
        if ($mime == 'application/octet-stream') {
            // Not good enough
            return null;
        } else if ($mime == 'audio/x-mod') {
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
     * Uses `file' on the command line with our custom magic.mime addition
     * to get the MIME type.
     */
    protected static function getMimeTypeWithCmdLineFileCustom($file) {
        $default = "/usr/share/file/magic";
        if (!file_exists($default)) {
            return null;
        }
        
        $cmd = binarypool_config::getUtilityPath('file');
        $magicfiles = escapeshellarg(API_PROJECT_DIR . "conf/magic/magic:$default");
        $cmd = "$cmd -m $magicfiles -ib " . escapeshellarg($file);
        $mime = trim(shell_exec($cmd));
        
        if ($mime == '' || $mime == 'regular file') {
            return null;
        } else {
            return $mime;
        }
    }
    
    /**
     * Uses `file' on the command line to get the MIME type.
     */
    protected static function getMimeTypeWithCmdLineFile($file) {
        $cmd = binarypool_config::getUtilityPath('file');
        $mime = trim(shell_exec("$cmd -ib " . escapeshellarg($file)));
        
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
