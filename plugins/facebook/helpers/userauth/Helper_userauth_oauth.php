<?php Load::HelperClass('userauth');

class Plugin_facebook_Helper_userauth_oauth extends Helper_userauth {
	protected $me = NULL;

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
		// If there was no code, or we cannot get a valid response, return false
		if(empty($input->get['code']) || empty($response)) {
			return false;
		}

		// Parse the response and save it to the session
		parse_str($response,$rarr);
		$_SESSION['facebook_access_token'] = $rarr['access_token'];
		$_SESSION['facebook_expires'] = NOW+$rarr['expires']-1; // Local unix time when token expires
		$_SESSION['facebook_query_string'] = $response;

		// Use the token to request facebook user information
		$this->me = $me = (array)json_decode(file_get_contents(
			'https://graph.facebook.com/me?access_token='.$rarr['access_token']));

		// If there is a local user with this facebook uid, log them in and redirect
		if ($user = Load::User(array('facebook_uid'=>$me['id']))) {
			$_SESSION['user_id'] = $user->id;
			return true;
		}

		// Setup registration vars (username, fullname, etc.)
		$vars = $this->registrationVars($me);

		// Load the facebook (oauth) userauth, register, and redirect
		if ($user = $this->register($vars['username'],'',$vars)) {
			$_SESSION['user_id'] = $user->id;
			return true;
		}
		return false;
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
	
	// Registration is pretty straightforward...
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

	function onLogin() {
		$USER = Load::User();
		if(empty($USER->facebook_uid)) { return; }

		// Let's track changes so we only save the model once!
		$doSave = FALSE;

		// If we're logged in via facebook, let's check /me for updates
		if ($token = @$_SESSION['facebook_access_token']) {
			if (is_null($this->me)) {
				$me = (array)json_decode(file_get_contents(
					'https://graph.facebook.com/me?access_token='.$token));
			} else {
				$me = $this->me;
			}
			if(empty($USER->facebook_username) && !empty($me['username'])) {
				$USER->facebook_username = $me['username'];
				$doSave = TRUE;
			}
			if ($USER->full_name==$USER->facebook_full_name) {
				$USER->full_name = $USER->facebook_full_name = $this->formatName($me);
				$doSave = TRUE;
			}
		}

		// If avatar is coming from FB or doesn't exist, grab it
		if (empty($USER->avatar_source) || $USER->avatar_source=='facebook') {
			$USER->avatar_url = 'http://graph.facebook.com/'.(
				$USER->facebook_username ? $USER->facebook_username : $USER->facebook_uid).'/picture';
			$USER->avatar_source = 'facebook';
			$doSave = TRUE;
		}
		if ($doSave) {
			$USER->save();
		}
	}

	protected function registrationVars($me) {
		$vars = array();
		$vars['full_name'] = $vars['facebook_full_name'] = $this->formatName($me);
		// If user has a facebook username and it doesn't exist locally, let them have it
		if (!empty($me['username']) && !Load::User(array('username'=>$me['username']))) {
			$vars['username'] = $me['username'];
		} else {
			// Otherwise give them something that should be unique based on their uid
			$vars['username'] = '@facebook:'.$me['id'];
		}
		$vars['facebook_uid'] = $me['id'];
		return $vars;
	}

	protected function formatName($me) {
		global $CFG;
		$format = @$CFG['facebook_name_format'];
		switch ($format) {
			case 'First': $name = $me['first_name']; break;
			case 'First Last':
				$name = $me['first_name'].' '.$me['last_name']; break;
			case 'First L.':
				$name = $me['first_name'].' '.$me['last_name'][0].'.'; break;
			case 'First L': default:
				$name = $me['first_name'].' '.$me['last_name'][0];
				break;
		}
		return $name;
	}
}