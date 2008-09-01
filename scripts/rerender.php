#!/usr/bin/env php
<?php
/**
 * Renders all uploaded renditions in a bucket again.
 * Done after important fixes or when a new rendition has been defined
 */

require_once(dirname(__FILE__) . '/../inc/api/init.php');
api_init::start();

// Command line
$max = 0;
if (count($argv) > 1 && is_numeric($argv[1])) {
    $max = intval($argv[1]);
    unset($argv[1]);
}

$buckets = array_slice($argv, 1);
if (count($buckets) == 0) {
    echo "Usage: " . $argv[0] . " [max] bucket...\n";
    echo "   max:  Maximum number of assets to convert.\n";
    exit(1);
}

if ($max > 0) {
    echo "Converting max. $max assets...\n";
}

$processed = 0;
$storage = null;
function walk_callback($dir) {
    global $processed, $storage, $max;
    
    if (file_exists($dir . 'index.xml')) {
        if ($max > 0) {
            echo $dir . "index.xml\n";
        }
        
        $asset = new binarypool_asset($dir . 'index.xml');
        $processed++;
        $storage->save($asset->getType(),
            array('_' => array('file' => $asset->getOriginal())));
    }
    
    if ($processed >= $max) {
        echo "Processed $processed files. Terminating.\n";
        exit(0);
    }
}

function walk_dir($root, $callback, $exclude = array()) {
    $root = rtrim($root, '/') . '/';
    $queue = array($root);
    foreach ( $exclude as &$path ) {
        $path = $root . trim($path, '/') . '/';
    }
    
    while ($base = array_shift($queue)) {
        $callback($base);
        
        if (($handle = opendir($base))) {
            while (($child = readdir($handle)) !== FALSE) {
                if (is_dir($base . $child) && $child != '.' && $child != '..') {
                    $combined_path = $base.$child.'/';
                    if (!in_array($combined_path, $exclude)) {
                        array_push($queue, $combined_path);
                    }
                }
            }
            closedir($handle);
        }
    }
}

foreach ($buckets as $bucket) {
    $storage = new binarypool_storage($bucket);

    printf("[%10s] Processing binaries.\n", $bucket);
    $processed = 0;
    walk_dir(binarypool_config::getRoot() . $bucket, 'walk_callback',
        array('created', 'expiry', 'downloaded'));
    
    printf("[%10s] %d binaries processed.\n", $bucket, $processed);
}
?>
