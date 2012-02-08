<?php 

$microtime_array = explode(' ',microtime());
define('NOW_MS',$microtime_array[0]);
define('NOW',$microtime_array[1]);
unset($microtime_array);

// Handy/lazy development debug function
function die_r($arr) {
	$args = func_get_args();
	if (sizeof($args)>1) { $arr = $args; }
	while (ob_get_level()) {
		ob_end_flush();
	}
	print '<pre>'; print_r($arr); die('</pre>');
}

if (!empty($_GET['_PATH_INFO'])) {
	if (empty($_SERVER['PATH_INFO'])) {
		$_SERVER['PATH_INFO'] = $_GET['_PATH_INFO'];
	}
	unset($_GET['_PATH_INFO'],$GLOBALS['_REQUEST']['_PATH_INFO']);
	$_SERVER['QUERY_STRING'] = http_build_query($_GET);
}
