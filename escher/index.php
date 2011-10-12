<?php if(!defined('CFG_LOADED')) { header('Location: ../'); die(); }

require_once('environment.php');
require_once('defaults.php');
require_once('loader.php');
Load::core('EscherObject.php','Controller.php','Helper.php','Model.php','patterns/Model_File.php');

if (!$CFG['maintenance_mode']) { $CFG = Load::Config(); } // Protects init CFG

$hooks = Load::Hooks();
$hooks->loadPluginHooks($CFG['active_plugins']);

if (isset($CFG['session']['type']) && $CFG['session']['type']!='none') {
	Load::Session();
	$hooks->runEvent('session_start');
}

if ($CFG['maintenance_mode']) {
	$acl = Load::ACL();
	if (!$acl->req('all',array('maintenance_mode','sysadmin'),'/')) {
		$CFG['root'] = array('controller'=>'errors','action'=>'maintenance','args'=>array('message'=>$CFG['maintenance_message']));
		$CFG['static_routes'] = array();
		$CFG['predefined_routes'] = $CFG['maintenance_mode_routes'];
	}
	$CFG = Load::Config(); // Protects init $CFG
}

$router = Load::Router();
if (!isset($router->controller)) {
	Load::Error('404');
}
$controller = Load::Controller($router->controller,@$router->args);
$controller->path = $router->getPath();
$controller->router = $router;
if (!$controller->execute() && empty($controller->data)) {
	Load::Error('404');
}

// Auto-display results
$controller->display($controller->getCalledAction(),$controller->data);
