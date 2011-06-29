<?php if(!defined('CFG_LOADED')) { header('Location: ../'); die(); } error_reporting(E_ALL);

if (empty($argv) || sizeof($argv) < 4) { return; }

$microtime_array = explode(' ',microtime());
define('NOW_MS',$microtime_array[0]);
define('NOW',$microtime_array[1]);
unset($microtime_array);

function die_r($arr) {
	$obs = ob_list_handlers();
	while (!empty($obs)) {
		ob_end_clean();
		$obs = ob_list_handlers();
	}
	print '<pre>'; print_r($arr); die('</pre>');
}

require_once(dirname(__FILE__).'/defaults.php');
require_once(dirname(__FILE__).'/loader.php');
Load::core('EscherObject.php','Controller.php','Helper.php','Model.php','patterns/Model_File.php');

$CFG = Load::Config(); // Protects init $CFG

$hooks = Load::Hooks();
$hooks->loadPluginHooks($CFG['active_plugins']);

array_shift($argv);
$plugin = array_shift($argv);
$controller = array_shift($argv);
if ($plugin!='-') {
	$controller = array($plugin,$controller);
}
$function = array_shift($argv);
$c_obj = Load::Controller($controller);
print_r($c_obj->$function($argv));
echo "\n";