<?php

abstract class Helper_userauth extends Helper {
	
	abstract function register($username,$password);
	
	function login($username,$password) { return false; }
	function resetPass($username,$password) { return false; }

	function authenticate() { return false; }
	function reauthenticate() { return true; }
	function deauthenticate() { return true; }
}