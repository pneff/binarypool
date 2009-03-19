<?php
$_SERVER['OKAPI_ENV'] = 'testing';

require_once("common.php");
require_once("../inc/api/init.php");
api_init::start();

// "Configuration"
$GLOBALS['TLD'] = 'binarypool.intra.local.ch';
if (file_exists('config_developer.php')) {
    include_once('config_developer.php');
}
if (getenv('TLD')) {
    $GLOBALS['TLD'] = getenv('TLD');
}

// Get command line arguments
$options = array();
$argvRemain = array();
foreach ($argv as $key=>$value) {
    if ($value[0] == '-') {
        $equal = strpos($value, '=');
        if ($equal !== false) {
            $valueName = substr($value, 1, $equal-1);
            $value = substr($value, $equal+1);
            $options[$valueName] = $value;
        }
    } else {
        array_push($argvRemain, $value);
    }
}

if ( in_array('text', $argvRemain) ) {
    $reporter = new TextReporter();
} else {
    $reporter = new JunitXMLReporter();
}

$files = array();
foreach($argvRemain as $file) {
    if ( !preg_match('#^(?:api|unit|functional)/.+\.php#', $file) ) { continue; }
    if ( !file_exists($file) ) { continue; }
    $files[] = $file;
}

$test = &new TestSuite("binarypool");

if ( count($files) ) {
    
    foreach ( $files as $file ) {
        $test->addFile($file);        
    }
    
} else {
    
    if ( !count($options) ) {
        $options['suite'] = 'all';
    }
    
    if ($options['suite'] == 'all' || $options['suite'] == 'api') {
        foreach (glob('api/*.php') as $file) {
            $test->addTestFile($file);
        }
    }
    
    if ($options['suite'] == 'all' || $options['suite'] == 'unit') {
        foreach (glob('unit/*.php') as $file) {
            $test->addTestFile($file);
        }
    }
    
    if ($options['suite'] == 'all' || $options['suite'] == 'functional') {
        foreach (glob('functional/*.php') as $file) {
            $test->addTestFile($file);
        }
    }
    
}

$_SERVER['BINARYPOOL_CONFIG'] = "test";
$result = $test->run($reporter);
if ($result === true || $result === 0) {
    exit(0);
} else {
    exit(1);
}
