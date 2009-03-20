#!/usr/bin/env php
<?php
ini_set("display_errors", "stderr");

/**
 * Reads messages from the preflight queue and calculates preflight
 * renditions.
 *
 * This script which is supposed to be kept running indefinitely.
 */

require_once(dirname(__FILE__) . '/../inc/api/init.php');
api_init::start();
require_once(dirname(__FILE__) . '/../inc/Amazon/SQS/Client.php');
require_once(dirname(__FILE__) . '/../inc/Amazon/SQS/Exception.php');

$AWS_access_id = '1468VYMBDWXZAGRNKM82';
$AWS_secret_key = 'sCAhD5rmhltn1qSRc8fvtgsN9SvnGfsVLwbgB7zO';
$QUEUE  = 'binarypool_preflight';
$conn = new Amazon_SQS_Client($AWS_access_id, $AWS_secret_key);
$res = $conn->getQueueAttributes(array(
    'QueueName' => $QUEUE,
    'AttributeName' => 'ApproximateNumberOfMessages',
));
$res = $res->getGetQueueAttributesResult();
$attr = $res->getAttribute();
echo $attr[0]->getValue() . "\n";
