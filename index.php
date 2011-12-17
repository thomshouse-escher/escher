<?php define('ESCHER_DOCUMENT_ROOT',dirname(__FILE__));

// Set error reporting
error_reporting(0); ini_set('display_errors',0);

// Initialize framework
require_once('escher/index.php');
$init = new EscherInit(dirname(__FILE__));
$init->main();

// That's all folks!
