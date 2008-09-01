<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/render_queue.php');

/**
 * Tests the binarypool_render_queue class.
 */
class BinarypoolRenderQueueTest extends BinarypoolTestCase {
    function testPutQueue() {
        $targetPath = self::$BUCKET . 'render/';
        $assetPath = $targetPath . 'index.xml';
        $sourceFile = $targetPath . 'test.jpg';
        $resizedFile = $targetPath . 'test_100_80';
        
        $out = binarypool_render_queue::render(
            $sourceFile, $resizedFile,
            $assetPath,
            array('server' => '__test__', '_bucket' => 'thebucket')
        );
        $this->assertNull($out);
        $this->assertEqual(count(binarypool_render_queue::$messages), 1);
        
        $message = binarypool_render_queue::$messages[0];
        $this->assertWithinMargin(time(), $message['tstamp'], 2);
        unset($message['tstamp']);
        $this->assertEqual($message,
            array('asset' => $assetPath,
                  'server' => '__test__',
                  'config' => array('server' => '__test__', '_bucket' => 'thebucket'),
                  'bucket' => 'thebucket',
            ));
    }
}
