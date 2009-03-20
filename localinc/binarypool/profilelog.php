<?php
/**
 * Override the parent - need to be able to log to two
 * targets 
 */
class binarypool_profilelog extends api_log {
    
    public static $profilelogger = null;
    
    /**
     * Reads it's configuration from the 'profilelog'
     * section of the configuration
     */
    public function __construct() {
        if (self::$profilelogger != null) {
            return;
        }
        
        self::$profilelogger = new Zend_Log();
        $configs = api_config::getInstance()->profilelog;
        
        if (is_null($configs) || count($configs) == 0) {
            self::$profilelogger->addWriter(new Zend_Log_Writer_Null());
            return;
        }
        
        foreach ($configs as $cfg) {
            $log = $this->createLogObject($cfg['class'], $cfg);
            self::$profilelogger->addWriter($log);
        }
    }
    
    public function __call($method, $params) {
        $prio = self::getMaskFromLevel($method);
        $message = array_shift($params);
        if (count($params) > 0) {
            $message = vsprintf($message, $params);
        }
        
        self::$profilelogger->log($message, $prio);
    }
    
}