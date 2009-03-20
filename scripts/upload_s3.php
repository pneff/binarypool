#!/usr/bin/env php
<?php
/**
 * Uploads a local file-based bucket to S3.
 */
require_once(dirname(__FILE__) . '/../inc/api/init.php');
api_init::start();
ini_set("display_errors", "stderr");
ini_set("memory_limit", "400M");

// Command line
$max = 0;
if (count($argv) > 1 && is_numeric($argv[1])) {
    $max = intval($argv[1]);
    unset($argv[1]);
}

$buckets = array_slice($argv, 1);
if (count($buckets) == 0) {
    echo "Usage: " . $argv[0] . " [max] bucket...\n";
    echo "   max:  Maximum number of files to upload.\n";
    exit(1);
}

if ($max > 0) {
    echo "Uploading max. $max files.\n";
}

$processed = 0;
$storage = null;
function walk_callback($bucket, $root, $file) {
    global $processed, $storage, $max;
    if ($max > 0 && $processed >= $max) {
        printf("[%10s] Processed %d files. Terminating.\n",
            $bucket, $processed);
        exit(0);
    }

    if (is_link(rtrim($root . $file, '/'))) {
        // Ignore symlinks
        return;
    }
    if (! file_exists($root . $file . 'index.xml')) {
        return;
    }
    if (!assert_filemtime($root . $file . 'index.xml', $bucket)) {
        return false;
    }

    printf("[%10s]   %s\n", $bucket, $file);
    $processed++;

    try {
        $asset = $storage->getAssetObject($file . 'index.xml');
        if (file_exists($asset->getOriginal())) {
            printf("[%10s]     Uploading file.\n", $bucket);
            $files = array('_' => array('file' => $asset->getOriginal()));
            foreach ($asset->getRenditions() as $key => $rendition) {
                if (file_exists($rendition)) {
                    // Local file
                    $files[$key] = array('file' => $rendition);
                }
            }
            $type = $asset->getType();
            if (!$storage->save($type, $files, true)) {
                printf("[%10s]     ERROR: Could not save asset.\n", $bucket);
                return;
            }
        } else {
            $asset->setBasePath($asset->getBasePath(), false);
            if (!$storage->saveAsset($asset, $file . 'index.xml')) {
                printf("[%10s]     ERROR: Could not save asset.\n", $bucket);
                return;
            }
            printf("[%10s]     Saved asset.\n", $bucket);
            binarypool_views::created($bucket, $file . 'index.xml',
                array('URL' => ''));
        }
    } catch (Exception $e) {
        printf("[%10s]     ERROR: Got exception while saving asset.\n", $bucket);
        return;
    }

    // Move to trash
    $basepath = $asset->getBasePath();
    $date = date('Y/m/d');
    $trashDir = 'Trash/' . $date . '/' . $basepath;
    $trashDirAbs = $root . $trashDir;
    $idx = 0;
    while (file_exists($trashDirAbs)) {
        $trashDirAbs = $root . $trashDir . '-' . $idx;
        $idx++;
    }
    printf("[%10s]     Moving file version to trash: %s\n", $bucket, $trashDirAbs);
    $trashParent = dirname($trashDirAbs);
    if (!file_exists($trashParent)) {
        mkdir($trashParent, 0755, true);
    }
    rename($root . $file, $trashDirAbs);
    symlink($trashDirAbs, rtrim($root . $file, '/'));
}

function assert_filemtime($file, $bucket) {
    $max_mtime = time() - 24*60*60;
    if (filemtime($file) > $max_mtime) {
        printf("[%10s] ERROR: %s was last touched %d hours ago.\n",
            $bucket, $file,
            (time() - filemtime($file)) / 60 / 60);
        return false;
    } else {
        return true;
    }
}

function walk_dir($bucket, $root, $callback, $exclude = array()) {
    $fsroot = binarypool_config::getRoot();
    $root = rtrim($root, '/') . '/';
    $queue = array($root);
    foreach ( $exclude as &$path ) {
        $path = $root . trim($path, '/') . '/';
    }

    while ($base = array_shift($queue)) {
        $relative = substr($base, strlen($fsroot));
        $callback($bucket, $fsroot, $relative);
        if (file_exists($base)) {
            if (($handle = opendir($base))) {
                while (($child = readdir($handle)) !== FALSE) {
                    if (is_dir($base . $child) && $child != '.' && $child != '..') {
                        $combined_path = $base.$child.'/';
                        if (!in_array($combined_path, $exclude)) {
                            array_unshift($queue, $combined_path);
                        }
                    }
                }
                closedir($handle);
            }
        }
    }
}

foreach ($buckets as $bucket) {
    $storage = new binarypool_storage($bucket);

    printf("[%10s] Processing files.\n", $bucket);
    $processed = 0;
    walk_dir($bucket, binarypool_config::getRoot() . $bucket, 'walk_callback',
        array('created', 'expiry', 'downloaded'));
    printf("[%10s] %d binaries processed.\n", $bucket, $processed);
}

