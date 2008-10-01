<?php
require_once(dirname(__FILE__).'/../../inc/Amazon/SQS/Client.php');
require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/render.php');

/**
 * Rendering class that just puts a command into a queue. An asynchronous
 * process then takes care of calculating the renditions.
 */
class binarypool_render_queue extends binarypool_render_base {
    /**
     * In-memory queue for testing.
     */
    public static $messages = array();
    
    /**
     * Config must provide the following keys:
     * - server: ActiveMQ server to connect to. Messages go into an in-memory
     *           queue if the server name is "__test__".
     * @return: null, as the rendition is generated asynchronously.
     */
    public static function render($source, $target, $assetFile, $config) {
        $bucket = $config['_bucket'];
        $queue = $config['queue'];
        
        $message = array(
            'asset'  => $assetFile,
            'config' => $config,
            'bucket' => $bucket,
            'tstamp' => time(),
        );
        
        if ($queue == '__test__') {
            array_push(self::$messages, $message);
        } else {
            $log = new api_log();
            $log->debug("Queueing message: $assetFile");
            
            $conn = new Amazon_SQS_Client($config['access_id'],
                $config['secret_key']);
            $response = $conn->sendMessage(array(
                'QueueName' => $queue,
                'MessageBody' => json_encode($message)));
            $result = $response->getSendMessageResult();
            
            $log->debug("Queued message ID %s for asset file %s",
                $result->getMessageId(), $assetFile);
        }
    }
}
