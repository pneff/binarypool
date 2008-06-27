<?php
class api_command_base extends api_command {
    protected $log = null;
    protected $viewStart = null;
    protected $ignoreView = false;
    
    public function __construct($route) {
        parent::__construct($route);
        
        $this->viewStart = microtime(true);
        $this->log = new api_log();
        $this->bucket = isset($route['bucket']) ? $route['bucket'] : '';
        $this->response->setCode(200);
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
        $this->log->debug("%s %s%s - Response time: %f seconds",
            $this->request->getVerb(),
            empty($_SERVER['HTTP_HOST']) ? '' : 'http://' . $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI'],
            microtime(true) - $this->viewStart);
    }
}
