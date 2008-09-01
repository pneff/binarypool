<?php
require_once(dirname(__FILE__).'/../../inc/stomp/Stomp.php');
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
        $server = $config['server'];
        $bucket = $config['_bucket'];
        
        $message = array(
            'asset'  => $assetFile,
            'server' => $server,
            'config' => $config,
            'bucket' => $bucket,
            'tstamp' => time(),
        );
        if ($server == '__test__') {
            array_push(self::$messages, $message);
        } else {
            self::queueMessage($message);
        }
    }
    
    protected static function queueMessage($message) {
        $server = $message['config']['server'];
        $queueName = isset($message['config']['queue']) ? $message['config']['queue'] : '/queue/test';
        
        $c = new StompConnection('tcp://' . $server . ':61613');
        $result = $c->connect();
        if ($result == null || $result->command != 'CONNECTED') {
            throw new binarypool_exception(123, 500, "Could not connect to the queue server $server");
        }
        
        $headers = array(
            'persistent' => 'true',
        );
        $msg = new MapMessage($message, $headers);
        $c->send($queueName, $msg);
    }
}
