<?php

function escher_echo($text,$default='') {
	Helper_output::doEcho($text,$default);
}

function escher_filter($text,$type,$default='') {
	$filter = Load::Filter();
	return $filter->filter($text,$type,$default);
}

function escher_run_event($event) {
	$args = func_get_args();
	$hooks = Load::Hooks();
	return call_user_func_array(array($hooks,'runEvent'),$args);
}

function escher_match_browser() {
	$args = func_get_args();
	return call_user_func_array(array('Helper_useragent','match'),$args);
}

function escher_clear_unload() {
	return Helper_output::clearUnload();
}

function escher_acl_check($resource=NULL,$action=NULL,$context=NULL,$inherit=TRUE) {
	$acl = Load::ACL();
	return $acl->check($resource,$action,$context,$inherit);
}

function escher_acl_require($resource=NULL,$action=NULL,$context=NULL,$inherit=TRUE) {
	$acl = Load::ACL();
	return $acl->req($resource,$action,$context,$inherit);
}

function escher_resolve_path($url,$absolute=TRUE) {
	$out = Helper_output_php::getCurrent();
	return $out->router->resolvePath($url,$absolute);
}