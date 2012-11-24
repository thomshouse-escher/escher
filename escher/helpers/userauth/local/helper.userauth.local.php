<?php

class Helper_userauth_local extends Helper_userauth {
    protected $authName = 'local';
    protected $loginField = 'username';
	protected $enctype = 'best';
	protected $legacy  = 'md5';

	function __construct($args=array()) {
        if (!empty($args['name'])) {
            $this->authName = $args['name'];
        }
        if (!empty($args['login'])
            && in_array($args['login'],array('email','username','both'))
        ) {
            $this->loginField = $args['login'];
        }
    }

    function login($username,$password) {
        if ($this->loginField = 'email'
            || ($this->loginField=='both'
                && filter_var($username,FILTER_VALIDATE_EMAIL)
            )
        ) {
            $loginField = 'email';
        } else {
            $loginField = 'username';
        }
		if (!$user = Load::Model('user',array($loginField => $username))) {
			return false;
		}
		if ($user->user_auth!=$this->authName) { return false; }
		$algo = !empty($user->enctype) ? $user->enctype : $this->legacy;
		$passhash = $this->encryptPassword($password,$user->user_id,$algo);
		if ($user->password==$passhash) {
			$best = $this->bestEncryption();
			if ($algo!=$best) {
				$user->assignVars(array(
					'password' => $this->encryptPassword(
						$password, $user->user_id, $best
					),
					'enctype'  => $best,
				));
				$user->save();
			}
			return $user;
		}
		return false;
	}

	function register($username,$password,$vars=array()) {
		$vars['username'] = $username;
		$vars['user_auth'] = $this->authName;
		$user = Load::Model('user');
		if ($user->register($vars)) {
			$best = $this->bestEncryption();
			$user->setValues(array(
				'password' => $this->encryptPassword(
					$password, $user->user_id, $best
				),
				'enctype'  => $best,
			));
			$user->save();
			return $user;
		}
		return false;
	}
	
	// Unimplemented
	// function resetPass($username,$password) { }
	
	function encryptPassword($password,$salt='',$algo=NULL) {
		if (is_null($algo)) { $algo = $this->bestEncryption(); }
		switch ($algo) {
			case 'bcrypt':
				$salt = '$2a$08$'.substr(md5($salt),0,21).'$';
				$password = crypt($password,$salt);
				break;
			case 'sha512':
				$salt = '$6$rounds=5000$'.substr(md5($salt),0,15).'$';
				$password = crypt($password,$salt);
				break;
			case 'sha256':
				$salt = '$5$rounds=5000$'.substr(md5($salt),0,15).'$';
				$password = crypt($password,$salt);
				break;
			case 'sha1':
				$password = sha1($password);
				break;
			case 'md5':
				$password = md5($password);
				break;
			default: $password = ''; break;
		}
		return $password;
	}

	function bestEncryption() {
		// If encryption is hard-coded, return it
		if ($this->enctype!='best') { return $this->enctype; }

		// Blowfish
		if (defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH) { return 'bcrypt'; }

		// SHA-512
		if (defined('CRYPT_SHA512') && CRYPT_SHA512) { return 'sha512'; }

		// SHA-256
		if (defined('CRYPT_SHA256') && CRYPT_SHA256) { return 'sha256'; }

		// SHA1
		return 'sha1';
	}
}