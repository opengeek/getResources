<?php
/**
 * getResources
 *
 * @package getResources
 * @version 1.0.0
 * @release beta
 * @author Jason Coward <modx@opengeek.com>
 */
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
// get rid of time limit
set_time_limit(0);

$root = dirname(dirname(__FILE__)) . '/';
$sources= array (
    'root' => $root,
    'build' => $root . '_build/',
    'lexicon' => $root . '_build/lexicon/',
    'assets' => $root . 'assets/',
);
unset($root);

// override with your own defines here (see build.config.sample.php)
require_once $sources['build'].'build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx= new modX();
$modx->initialize('mgr');
$modx->setLogLevel(MODX_LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

$name = 'getresources';
$version = '1.0.0';
$release = 'beta-1';

$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage($name, $version, $release);
//$builder->registerNamespace('getresources',false,true,'{core_path}components/getresources/');


// get the source from the actual snippet in your database OR
// manually create the object, grabbing the source from a file
$c= $modx->newObject('modSnippet');
$c->set('name', 'getResources');
$c->set('description', '<strong>1.0.0-beta-1</strong> A general purpose Resource listing and summarization snippet for MODx Revolution');
$c->set('category', 0);
$c->set('snippet', file_get_contents($sources['assets'] . 'snippet.getresources.php'));

// create a transport vehicle for the data object
$attributes= array(XPDO_TRANSPORT_UNIQUE_KEY => 'name');
$vehicle = $builder->createVehicle($c, $attributes);
$vehicle->resolve('file',array(
    'source' => $sources['assets'] . 'getresources',
    'target' => "return MODX_CORE_PATH . 'components/';",
));
$builder->putVehicle($vehicle);

// load lexicon strings
//$builder->buildLexicon($sources['lexicon']);

// zip up the package
$builder->pack();

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

$modx->log(MODX_LOG_LEVEL_INFO,"Package Built.\nExecution time: {$totalTime}");
exit();
