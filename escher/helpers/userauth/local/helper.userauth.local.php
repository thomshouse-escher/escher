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
        // Login via username or email based on configuration
		if ($this->loginField=='email'
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

		// Make sure user belongs to this auth method
		if ($user->user_auth!=$this->authName) { return false; }
		// Determine the encryption algorithm to use
		$algo = !empty($user->enctype) ? $user->enctype : $this->legacy;

		// Attempt to use creation time as seed
		$passhash = $this->encryptPassword(
			$password,
			strtotime($user->created_at),
			$algo
		);

		// If creation time seed fails, fall back to user_id
		if ($user->password!=$passhash) {
			$passhash = $this->encryptPassword(
				$password,$user->user_id,$algo
			);
			$legacyhash = TRUE; // Track legacy user
		}
		if ($user->password==$passhash) { // Passwords match
			// Convert user to more secure encryption if available
			$best = $this->bestEncryption();
			if ($algo!=$best || !empty($legacyhash)) {
				$user->assignVars(array(
					'password' => $this->encryptPassword(
						$password,
						strtotime($user->created_at),
						$best
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
			$user->touch();
			$user->setValues(array(
				'password' => $this->encryptPassword(
					$password, strtotime($user->created_at), $best
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
			case 'cleartext': break;
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
