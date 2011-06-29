<?php

class Helper_userauth_local extends Helper_userauth {
	protected $enctype = 'md5';

	function login($username,$password) {
		$password = $this->encryptPassword($password);
		if (!$user = Load::Model('user',array('username' => $username,'password' => $password))) {
			return false;
		}
		return true;
	}

	function register($username,$password,$vars=array()) {
			$password = $this->encryptPassword($password);
			$user = Load::Model('user');
			$vars['username'] = $username;
			$vars['password'] = $password;
			$vars['auth'] = 'local';
			$user->assignVars($vars);
			$user->save();
	}
	
	// Unimplemented
	// function resetPass($username,$password) { }
	
	protected function encryptPassword($password) {
		switch ($this->enctype) {
			default: case 'md5':
				$password = md5($password);
				break;
			case 'sha1':
				$password = sha($password);
				break;
		}
		return $password;
	}
}