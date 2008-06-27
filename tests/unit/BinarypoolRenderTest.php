<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/render.php');

/**
 * Tests the binarypool_render class.
 */
class BinarypoolRenderTest extends BinarypoolTestCase {
    /**
     * Tests that the renditions get generated and saved.
     */
    function testImageRenditions() {
        $dir = self::$BUCKET . 'tmpdir/';
        mkdir($dir, 0755, true);
        copy($this->testfile, $dir . 'golf.jpg');

        $renditions = binarypool_render::render('IMAGE', 'test', $dir . 'golf.jpg', $dir);
        $this->assertEqual($renditions, array(
            'detailpage' => $dir . 'detailpage.jpg',
            'resultlist' => $dir . 'resultlist.jpg',
        ));
        
        $this->assertTrue(file_exists($dir . 'golf.jpg'),
            'Original file was not written to file system.');
        $this->assertTrue(file_exists($dir . 'resultlist.jpg'),
            '"resultlist" rendition was not written to file system.');
        $this->assertTrue(file_exists($dir . 'detailpage.jpg'),
            '"detailpage" rendition was not written to file system.');
    }

    /**
     * Tests that the renditions for a movie get generated and saved.
     */
    function testMovieRenditions() {
        $dir = self::$BUCKET . 'tmpdir/';
        mkdir($dir, 0755, true);
        copy(realpath(dirname(__FILE__).'/../res/swiss-kurier.swf'), $dir . 'swiss.swf');

        $renditions = binarypool_render::render('MOVIE', 'test', $dir . 'swiss.swf', $dir);
        
        // No renditions
        $this->assertEqual($renditions, array());
    }
}
?>
