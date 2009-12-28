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
set_time_limit(0);

/* define sources */
$root = dirname(dirname(__FILE__)) . '/';
$sources= array (
    'root' => $root,
    'build' => $root . '_build/',
    'lexicon' => $root . '_build/lexicon/',
    'source_core' => $root . 'core/components/getresources',
);
unset($root);

/* instantiate MODx */
require_once $sources['build'].'build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
$modx= new modX();
$modx->initialize('mgr');
$modx->setLogLevel(xPDO::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

/* set package info */
define('PKG_NAME','getresources');
define('PKG_VERSION','1.0.0');
define('PKG_RELEASE','beta3');

/* load builder */
$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAME, PKG_VERSION, PKG_RELEASE);
//$builder->registerNamespace('getresources',false,true,'{core_path}components/getresources/');

/* create snippet object */
$modx->log(xPDO::LOG_LEVEL_INFO,'Adding in snippet.'); flush();
$snippet= $modx->newObject('modSnippet');
$snippet->set('name', 'getResources');
$snippet->set('description', '<strong>'.PKG_VERSION.'-'.PKG_RELEASE.'</strong> A general purpose Resource listing and summarization snippet for MODx Revolution');
$snippet->set('category', 0);
$snippet->set('snippet', file_get_contents($sources['source_core'] . '/snippet.getresources.php'));
$properties = include $sources['build'].'properties.inc.php';
$snippet->setProperties($properties);
unset($properties);


/* create a transport vehicle for the data object */
$vehicle = $builder->createVehicle($snippet,array(
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::UNIQUE_KEY => 'name',
));
$vehicle->resolve('file',array(
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
));
$builder->putVehicle($vehicle);

/* load lexicon strings */
//$builder->buildLexicon($sources['lexicon']);

/* zip up the package */
$builder->pack();

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

$modx->log(xPDO::LOG_LEVEL_INFO,"\n<br />Package Built.<br />\nExecution time: {$totalTime}\n");
exit();
