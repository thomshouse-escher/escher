<?php

class Helper_input extends Helper {
	function __construct() {
		parent::__construct();
		$this->get = $_GET;
		$this->post = $_POST;
		$this->cookie = $_COOKIE;
		$this->request = $_REQUEST;
		if (TRUE || ini_get('magic_quotes_gpc')) {
			array_walk_recursive($this->get,array($this,'oninit_stripslashes'));
			array_walk_recursive($this->post,array($this,'oninit_stripslashes'));
			array_walk_recursive($this->cookie,array($this,'oninit_stripslashes'));
			array_walk_recursive($this->request,array($this,'oninit_stripslashes'));
		}
	}
	
	function get($key,$filter='string',$default=NULL) {
		$filterObject = Load::Filter();
		return $filterObject->filter(@$this->get[$key],$filter,$default);
	}

	function post($key,$filter='string',$default=NULL) {
		$filterObject = Load::Filter();
		return $filterObject->filter(@$this->post[$key],$filter,$default);
	}

	function cookie($key,$filter='string',$default=NULL) {
		$filterObject = Load::Filter();
		return $filterObject->filter(@$this->cookie[$key],$filter,$default);
	}

	function request($key,$filter='string',$default=NULL) {
		$filterObject = Load::Filter();
		return $filterObject->filter(@$this->request[$key],$filter,$default);
	}

	function isAjax() {
		if (!isset($_SERVER['X-Requested-With'])) {
			return false;
		}
		return (bool)($_SERVER['X-Requested-With']=='XMLHttpRequest');			
	}
	
	protected function oninit_stripslashes(&$arr) {
		$arr = stripslashes($arr);
	}
}