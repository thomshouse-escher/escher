<?php

// Set error reporting
error_reporting(E_ALL); ini_set('display_errors',1);

// Initialize framework
require_once('escher/index.php');
$init = new EscherInit(dirname(__FILE__));
$init->main();

// That's all folks!
