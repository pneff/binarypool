#!/usr/bin/env php
<?php
/**
 * Deletes expired assets. This script is meant to run
 * daily. The user as whom this script is run must have
 * full write permissions on the Binary Pool file system so
 * it can delete files.
 */

define('API_PROJECT_DIR', realpath(dirname(__FILE__)."/../"));
require_once(dirname(__FILE__) . '/../localinc/binarypool/config.php');
require_once(dirname(__FILE__) . '/../localinc/binarypool/browser.php');
require_once(dirname(__FILE__) . '/../localinc/binarypool/storage.php');
require_once(dirname(__FILE__) . '/../localinc/binarypool/expiry.php');

function cleanSymlinks() {
    // Do symlink cleanup
    printf("[%10s] Cleaning up symlinks.\n", 'FINAL');
    $cmd = binarypool_config::getUtilityPath('symlinks');
    system("$cmd -cdrs " . binarypool_config::getRoot() . "*/created");
    system("$cmd -cdrs " . binarypool_config::getRoot() . "*/expiry");
}

cleanSymlinks();

// Walk through each bucket
$buckets = binarypool_config::getBuckets();
foreach (array_keys($buckets) as $bucket) {
    $storage = new binarypool_storage($bucket);

    printf("[%10s] Fetching list of expired binaries.\n", $bucket);
    $expired = binarypool_browser::getExpired($bucket);

    printf("[%10s] %d expired.\n", $bucket, $expired);
    foreach ($expired as $asset) {
        try {
            if (binarypool_expiry::isExpired($bucket, $asset)) {
                printf("[%10s] Deleting %s\n", $bucket, $asset);
                $storage->delete($asset);
            }
        } catch (binarypool_exception $e) {
            if ($e->getCode() == 112) {
                printf("[%10s] Asset does not exist %s\n", $bucket, $asset);
            } else {
                throw $e;
            }
        }
    }

    printf("[%10s] Done.\n", $bucket, $expired);
}

cleanSymlinks();
?>
