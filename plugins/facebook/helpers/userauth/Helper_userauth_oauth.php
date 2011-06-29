<?php Load::HelperClass('userauth');

class Plugin_facebook_Helper_userauth_oauth extends Helper_userauth {
	function authenticate() {
		$headers = Load::Headers();

		// User should be reaching this URL from a Facebook Oauth response
		// If user authorized site, query string will include "code"
		$input = Load::Input();
		if(!empty($input->get['code'])) {
			global $CFG;
			// Attempt to get Oauth access token using the provided code
			$response = @file_get_contents('https://graph.facebook.com/oauth/access_token?client_id='.$CFG['facebook_appId'].
				'&redirect_uri='.urlencode($CFG['wwwroot'].'/login/facebook/').
				'&client_secret='.$CFG['facebook_secret'].
				'&code='.$input->get['code']);
		}
		// If there was no code, or we cannot get a valid response, just redirect
		if(empty($input->get['code']) || empty($response)) {
			$headers->redirect();
		}

		// Parse the response and save it to the session
		parse_str($response,$rarr);
		$_SESSION['facebook_access_token'] = $rarr['access_token'];
		$_SESSION['facebook_expires'] = NOW+$rarr['expires']-1; // Local unix time when token expires
		$_SESSION['facebook_query_string'] = $response;

		// Use the token to request facebook user information
		$me = (array)json_decode(file_get_contents('https://graph.facebook.com/me?access_token='.$rarr['access_token']));

		// If there is a local user with this facebook uid, log them in and redirect
		if ($user = Load::User(array('facebook_uid'=>$me['id']))) {
			$_SESSION['user_id'] = $user->id;
			$user->assignVars($this->loginVars($me));
			$user->save();
			$headers->redirect();
		}
		
		// If the user doesn't exist, we need to register
		if (!empty($me['username']) && !Load::User(array('username'=>$me['username']))) {
			// If user has a facebook username and it's not already taken in the system, let them have it
			$username = $me['username'];
		} else {
			$username = '@facebook:'.$me['id'];
		}

		// Setup registration vars (username, fullname, etc.)
		$vars = $this->registrationVars($me);

		// Load the facebook (oauth) userauth, register, and redirect
		if ($user = $this->register($vars['username'],'',$vars)) {
			$_SESSION['user_id'] = $user->id;
		}
		$headers->redirect();
	}

	// If our token expires, we need to redirect the user to authenticate with facebook
	function reauthenticate($force=FALSE) {
		if ($force || empty($_SESSION['facebook_expires']) || $_SESSION['facebook_expires']<NOW) {
			unset($_SESSION['user_id']);
			global $CFG;
			$headers = Load::Headers();
			$headers->redirect('https://www.facebook.com/dialog/oauth?client_id='.$CFG['facebook_appId'].
				'&redirect_uri='.urlencode($CFG['wwwroot'].'/login/facebook/'));
		}
		return true;
	}

	// Nothing to do here, session is entirely local
	function deauthenticate() { return true; }
	
	// Registration is pretty straight forward...
	function register($username,$password=NULL,$vars=array()) {
			// We must know the facebook uid to register a facebook user
			if (!isset($vars['facebook_uid'])) {
				return false;
			}
			// Password will not get used, fill it with noise
			$password = sha1($username.time());
			
			// Assign vars to the user and save
			$user = Load::Model('user');
			$vars['username'] = $username;
			$vars['password'] = $password;
			$vars['auth'] = 'facebook'; // fbconnect auth and oauth are either/or
			$user->assignVars($vars);
			if ($user->save()) {
				return $user;
			} else {
				return false;
			}
	}

	protected function registrationVars($me) {
		$vars = $this->loginVars($me);
		// If the user doesn't exist, we need to register
		if (!empty($me['username']) && !Load::User(array('username'=>$me['username']))) {
			// If user has a facebook username and it's not already taken in the system, let them have it
			$vars['username'] = $me['username'];
		} else {
			// Otherwise give them something that should be unique based on their uid
			$vars['username'] = '@facebook:'.$me['id'];
		}
		$vars['facebook_uid'] = $me['id'];
		return $vars;
	}

	protected function loginVars($me) {
		$vars = array();
		$vars['full_name'] = $me['first_name'].' '.$me['last_name'][0];
		if(!empty($me['username'])) { $vars['facebook_username'] = $me['username']; }
		$vars['icon_url'] = 'http://graph.facebook.com/'.($me['username'] ? $me['username'] : $me['id']).'/picture';
		return $vars;
	}
}