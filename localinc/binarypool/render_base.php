<?php
/**
 * Base class for rendition classes.
 */
abstract class binarypool_render_base {
    /**
     * Process an image.
     *
     * @param $source: Absolute file path to the original file.
     * @param $target: Absolute file path without extension to the new file.
     * @param $assetFile: Path to the asset file. Needed for asynchronous
     *                    rendition processes.
     * @param $config: Rendition configuration. The rendition hash from
     *                 the configuration is passed in. The keys depend
     *                 on the subclass.
     * @return: The absolute file path to the rendition including an
     *          appropriate extension or null if no rendition was generated.
     */
    abstract public static function render($source, $target, $assetFile, $config);
}
