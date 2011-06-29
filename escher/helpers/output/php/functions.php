<?php

function __esc_output_php_echo($text,$default='') {
	Helper_output::doEcho($text,$default);
}

function __esc_output_php_filter($text,$type,$default='') {
	$filter = Load::Filter();
	echo $filter->filter($text,$type,$default);
}

function __esc_output_run_event($event) {
	$args = func_get_args();
	array_shift($args);
	$hooks = Load::Hooks();
	return $hooks->runEvent($event,$args);
}

function __esc_output_php_match_browser() {
	$args = func_get_args();
	return call_user_func_array(array('Helper_useragent','match'),$args);
}

function __esc_output_clear_unload() {
	return Helper_output::clearUnload();
}

function __esc_output_acl_check($resource=NULL,$action=NULL,$context=NULL,$inherit=TRUE) {
	$acl = Load::ACL();
	return $acl->check($resource,$action,$context,$inherit);
}

function __esc_output_acl_require($resource=NULL,$action=NULL,$context=NULL,$inherit=TRUE) {
	$acl = Load::ACL();
	return $acl->req($resource,$action,$context,$inherit);
}