<?php
require_once(dirname(__FILE__) . '/../../../inc/S3.php');

class api_command_base extends api_command {
    protected $log = null;
    protected $viewStart = null;
    protected $ignoreView = false;
    protected $clientxsl = TRUE;
    
    public function __construct($route) {
        parent::__construct($route);
        
        $this->viewStart = microtime(true);
        //$this->log = new api_log();
        $this->log = new api_log();
        $this->bucket = isset($route['bucket']) ? $route['bucket'] : '';
        $this->response->setCode(200);
        $this->response->setContentLengthOutput(true);
        $this->clientxsl = !isset($_GET['NOXSL']);
        array_push($this->data, new api_model_xml(
            sprintf('<clientxsl>%s</clientxsl>', intval($this->clientxsl))));
    }
    
    public function getData() {
        $this->logRequest();
        return parent::getData();
    }
    
    public function process() {
        try {
             
            return $this->execute();
        
        } catch (binarypool_exception $e) {
            // Error which includes HTTP code
            $this->log->info("binarypool_exception: Error code: %d, HTTP code: %d, message: %s",
                $e->getCode(), $e->getHttpCode(), $e->getMessage());
            $this->setResponseCode($e->getHttpCode());
            
            $xml = '<status type="error" error="' . $e->getCode() . '"><msg>' .
                htmlspecialchars($e->getMessage()) . 
                '</msg></status>';
            array_push($this->data, new api_model_xml($xml));
        
        } catch (S3Exception $e) {
            $this->log->err("S3 Exception: Error code: %d, message: %s",
                $e->getCode(), $e->getMessage());
            $this->setResponseCode(500);
            $xml = "<status type='error' error='125'><msg>" . $e->getMessage() . "</msg></status>";
            array_push($this->data, new api_model_xml($xml));
            
        } catch (Exception $e) {
            // Generic error
            $this->log->err("Exception: Error code: %d, message: %s",
                $e->getCode(), $e->getMessage());
            $this->setResponseCode(500);
            
            $xml = "<status type='error'><msg>" . $e->getMessage() . "</msg></status>";
            array_push($this->data, new api_model_xml($xml));
        }
    }
    
    protected function setResponseCode($code) {
        $this->response->setCode($code);
    }

    public function getXslParams() {
        if ($this->ignoreView) {
            // this->getData() will never get called, so log here.
            $this->logRequest();
        }
        
        return array('ignore' => $this->ignoreView);
    }
    
    protected function logRequest() {
        $log = new binarypool_profilelog();
        $log->info("%s %s%s - Response time: %f seconds, Peak Mem Use: %s bytes",
            $this->request->getVerb(),
            empty($_SERVER['HTTP_HOST']) ? '' : 'http://' . $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI'],
            microtime(true) - $this->viewStart,
            memory_get_peak_usage()
            );
    }
}
