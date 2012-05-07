<?php define('ESCHER_DOCUMENT_ROOT',dirname(__FILE__));

// Require cli SAPI
if (php_sapi_name()!='cli') {
	header('-',TRUE,501);
	exit;
}

// Require command line arguments for plugin, controller and method
if (empty($_SERVER['argv']) || sizeof($_SERVER['argv']) < 4) {
	echo "Insufficient arguments\n";
	return;
}

// Special high-priority logic for Escher job scheduler
$argv = implode(' ',array_slice($_SERVER['argv'],0,4));
if (strpos($argv,'proc.php - scheduler process')!==FALSE) {
	// If fourth argument is provided, delay processing for specified amount of time
	if ($_SERVER['argc']>=5 && is_numeric($_SERVER['argv'][4]) && $_SERVER['argv'][4]>0) {
		sleep(min(ceil($_SERVER['argv'][4]),59));
	}
	// Determine whether or not scheduler is already processing on this server
	exec('ps aux | grep "proc.php - scheduler process" | grep -v grep',$ps);
	if (sizeof($ps)>1) {
		echo "Already running.\n";
		return;
	}
}

// Set error reporting
error_reporting(E_ALL); ini_set('display_errors',1);

// Initialize framework
require_once('escher/index.php');
$init = new EscherInit();
$init->proc();
