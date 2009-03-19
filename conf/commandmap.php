<?php
$m = new api_routing();

// Default rule (template)
$default = new api_routing_route();
$default->route('/:bucket/+asset')
        ->config(array('view' => array('class' => 'xmlhead', 'xsl' => 'binarypool.xsl')));

// https://wiki.local.ch/display/I3/Delete+binary
$m->add($default->dup()
    ->when(array('verb' => 'DELETE'))
    ->config(array('command' => 'delete')));

// https://wiki.local.ch/display/I3/Update+modification+date
$m->add($default->dup()
    ->when(array('verb' => 'POST'))
    ->config(array('command' => 'touch')));

// https://wiki.local.ch/display/I3/Get+asset+by+SHA1+hash
$cmd = $m->add($default->dup()
    ->when(array('verb' => 'GET'))
    ->route('/:bucket/sha1/:hash')
    ->config(array('command' => 'sha1')));
$m->add($cmd->dup()->when(array('verb' => 'HEAD')));

// https://wiki.local.ch/display/I3/List+buckets
$cmd = $m->add($default->dup()
    ->when(array('verb' => 'GET'))
    ->route('/')
    ->config(array('command' => 'buckets')));
$m->add($cmd->dup()->when(array('verb' => 'HEAD')));

// https://wiki.local.ch/display/I3/List+assets+by+creation+date
$cmd = $m->add($default->dup()
    ->when(array('verb' => 'GET'))
    ->route('/:bucket/created/:year/:month/:day')
    ->config(array('command' => 'view', 'viewname' => 'created')));
$m->add($cmd->dup()->when(array('verb' => 'HEAD')));

// Assets by expiry date
$cmd = $m->add($default->dup()
    ->when(array('verb' => 'GET'))
    ->route('/:bucket/expiry/:year/:month/:day')
    ->config(array('command' => 'view', 'viewname' => 'expiry')));
$m->add($cmd->dup()->when(array('verb' => 'HEAD')));

// Assets downloaded by hash of source URL
$cmd = $m->add($default->dup()
    ->when(array('verb' => 'GET'))
    ->route('/:bucket/downloaded/:prefix')
    ->config(array('command' => 'view', 'viewname' => 'downloaded')));
$m->add($cmd->dup()->when(array('verb' => 'HEAD')));

// Assets downloaded by hash of source URL - allow HEAD requests
// to test for existance without needing to parse XML
$cmd = $m->add($default->dup()
    ->when(array('verb' => 'GET'))
    ->route('/:bucket/downloaded/:prefix/:hash')
    ->config(array('command' => 'view', 'viewname' => 'downloaded')));
$m->add($cmd->dup()->when(array('verb' => 'HEAD')));

// Migration path for binarypool serving
$cmd = $m->add($default->dup()
    ->route('/migrate/:stage/priv/:bucket/+asset')
    ->when(array('verb' => 'GET'))
    ->config(array('command' => 'migrationserve', 'area' => 'priv')));
$m->add($cmd->dup()->when(array('verb' => 'HEAD')));
$cmd = $m->add($default->dup()
    ->route('/migrate/:stage/:bucket/+asset')
    ->when(array('verb' => 'GET'))
    ->config(array('command' => 'migrationserve', 'area' => 'web')));
$m->add($cmd->dup()->when(array('verb' => 'HEAD')));

// https://wiki.local.ch/display/I3/Get+binary+or+asset
$cmd = $m->add($default->dup()
    ->when(array('verb' => 'GET'))
    ->config(array('command' => 'serve')));
$m->add($cmd->dup()->when(array('verb' => 'HEAD')));

// https://wiki.local.ch/display/I3/Create+new+binary
$m->add($default->dup()
    ->when(array('verb' => 'POST'))
    ->route('/:bucket')
    ->config(array('command' => 'create')));
    
$m->add($default->dup()
    ->when(array('verb' => 'GET'))
    ->route('/:bucket')
    ->config(array('command' => 'bucket')));

// Fallback
$m->add($default->dup()->route('/*')->config(array('command' => 'fallback')));
