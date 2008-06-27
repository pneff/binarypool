<?php
require_once(dirname(__FILE__).'/../../binarypool/exception.php');

class api_command_fallback extends api_command_base {
    protected function execute() {
        $allowedVerbs = array('GET', 'HEAD', 'POST', 'DELETE');
        $verb = $this->request->getVerb();
        if (! in_array($verb, $allowedVerbs)) {
            throw new binarypool_exception(114, 400, "Unknown HTTP verb: $verb");
        } else {
            throw new binarypool_exception(101, 400, "No bucket given in the path.");
        }
    }
}
?>
