<?php

abstract class Helper_userauth extends Helper {
	
	abstract function register($username,$password);
	
	function login($username,$password) { return false; }
	function resetPass($username,$password) { return false; }

	function authenticate() { return false; }
	function reauthenticate() { return true; }
	function deauthenticate() { return true; }

	function usernameIsAvailable($username) {
		// If username is in use, return false
		if (Load::User(array('username'=>$username))) { return FALSE; }
		$config = Load::Config();
		// If username is reserved, return false
		if (preg_grep(
			'/^'.preg_quote($username).'$/i',
			$config['reserved_usernames']
		)) { return FALSE; }
		// Otherwise, return true
		return TRUE;
	}
}