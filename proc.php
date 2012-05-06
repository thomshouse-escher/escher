<?php define('ESCHER_DOCUMENT_ROOT',dirname(__FILE__));

// Set error reporting
error_reporting(E_ALL); ini_set('display_errors',1);

// Initialize framework
require_once('escher/index.php');
$init = new EscherInit();
$init->proc();
