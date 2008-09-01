<?php
require_once("BinarypoolTestCase.php");
require_once(dirname(__FILE__).'/../../localinc/binarypool/render_image.php');

/**
 * Tests the binarypool_render_image class.
 */
class BinarypoolRenderImageTest extends BinarypoolTestCase {
    /**
     * Creates a max. 100x80 rendition of the given file.
     */
    function testRender100x80() {
        $targetPath = self::$BUCKET . 'render/';
        $sourceFile = $targetPath . 'test.jpg';
        $resizedFile = $targetPath . 'test_100_80';
        
        mkdir($targetPath, 0755, true);
        copy($this->testfile, $targetPath . 'test.jpg');
        
        $out = binarypool_render_image::render(
            $sourceFile, $resizedFile,
            null, array('width' => 100, 'height' => 80)
        );
        $this->assertEqual($out, $resizedFile . '.jpg', 'Rendering did not determine the correct file extension for the thumbnail. - %s');
        
        $this->assertTrue(file_exists($out), "Resized file was not created");
        list ($width, $height) = getimagesize($out);
        $this->assertEqual(100, $width);
        $this->assertEqual(62, $height);
    }
    
    /**
     * Creates a max. 800x800 rendition of the given file.
     * This should not change the original at all as it's a bigger rendition.
     * We only scale down.
     */
    function testRender800x800() {
        $targetPath = self::$BUCKET . 'render/';
        $sourceFile = $targetPath . 'test.jpg';
        $resizedFile = $targetPath . 'test_800_800';
        
        mkdir($targetPath, 0755, true);
        copy($this->testfile, $targetPath . 'test.jpg');
        
        $out = binarypool_render_image::render(
            $sourceFile, $resizedFile,
            null, array('width' => 800, 'height' => 800)
        );
        $this->assertEqual($out, $resizedFile . '.jpg', 'Rendering did not determine the correct file extension for the thumbnail. - %s');
        
        $this->assertTrue(file_exists($out), "Resized file was not created");
        list ($width, $height) = getimagesize($out);
        $this->assertEqual(557, $width);
        $this->assertEqual(344, $height);
    }
    
    /**
     * Creates a rendition of a PDF file.
     */
    function testRenderPDF() {
        $targetPath = self::$BUCKET . 'render/';
        $sourceFile = dirname(__FILE__) . '/../res/emil_frey_logo.pdf';
        $resizedFile = $targetPath . 'test_pdf';
        
        mkdir($targetPath, 0755, true);
        
        $out = binarypool_render_image::render(
            $sourceFile, $resizedFile,
            null, array('width' => 300, 'height' => 300)
        );
        $this->assertEqual($out, $resizedFile . '.png', 'Rendering did not determine the correct file extension for the thumbnail. - %s');
        
        $this->assertTrue(file_exists($out), "Resized file was not created");
        list ($width, $height) = getimagesize($out);
        $this->assertEqual(92, $width);
        $this->assertEqual(21, $height);
    }
}
?>
