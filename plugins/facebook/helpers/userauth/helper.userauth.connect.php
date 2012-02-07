<?php Load::HelperClass('userauth'); Load::lib(array('facebook','facebook.php'));

class Plugin_facebook_Helper_userauth_connect extends Helper_userauth {
	function authenticate() {
		$headers = Load::Headers();
		$hooks = Load::Hooks();

		// Load FB API and session
		$fb = loadFacebookAPI();
		$fbsession = $fb->getSession();
		if (!$fbsession) {
			// If no FB session is found, just redirect
			return false;
		}
		// Facebook API uses strict error handling
		try {
			// Attempt to get uid and "me" data
			$uid = $fb->getUser();
			$me = $fb->api('/me');
		} catch (FacebookApiException $e) {
			return false;
		}

		// If there is a local user with this facebook uid, log them in and redirect
		if ($user = Load::User(array('facebook_uid'=>$uid))) {
			$_SESSION['user_id'] = $user->id;
			$hooks->runEvent('register_success');
			return true;
		}
		
		// Setup registration vars (username, fullname, etc.)
		$vars = $this->registrationVars($me);
		
		// Load the fbconnect userauth, register and redirect
		if ($user = $this->register($vars['username'],'',$vars)) {
			$_SESSION['user_id'] = $user->id;
			return true;
		}
		return false;
	}

	// fbconnect userauth is very closely tied to a user's FB session, reauth is essential.
	function reauthenticate() {
		// Basically, load everything and make sure that the user is still logged in
		$fb = loadFacebookAPI();
		$fbsession = $fb->getSession();
		if ($fbsession) {
			try {
				$uid = $fb->getUser();
				$me = $fb->api('/me');
			} catch (FacebookApiException $e) {
				return false;
			}
		}
		if (!$me) { return false; }

		// Oh yeah, and we should probably ensure that this is the same user
		$user = Load::User();
		if (empty($user->facebook_uid) || $uid != $user->facebook_uid) {
			return false;
		}
		return true;
	}

	// Because fbconnect is tied to FB session, to log out locally we must log out of FB
	function deauthenticate() {
		// ...which means we have to load everything *again*
		$fb = loadFacebookAPI();
		$fbsession = $fb->getSession();
		if ($fbsession) {
			try {
				$uid = $fb->getUser();
				$me = $fb->api('/me');
			} catch (FacebookApiException $e) {
				error_log($e);
			}
		}
		// ...just to make sure we are logged in
		if (!empty($me)) {
			$headers = Load::Headers();
			// ...JUST to logout.  Really, why aren't you using Oauth?
			$headers->redirect($fb->getLogoutUrl()); exit();
		}
		return true;
	}
	
	// Registration is pretty straight forward...
	function register($username,$password=NULL,$vars=array()) {
		$hooks = Load::Hooks();

		// We must know the facebook uid to register a facebook user
		if (!isset($vars['facebook_uid'])) {
			$hooks->runEvent('register_failure');
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
			$hooks->runEvent('register_failure');
			return false;
		}
	}

	function onLogin() {
		$USER = Load::User();
		if(empty($USER->facebook_uid)) { return; }

		// Let's track changes so we only save the model once!
		$doSave = FALSE;

		if ($USER->auth=='facebook') {
			// Load FB API and session
			$fb = loadFacebookAPI();
			$fbsession = $fb->getSession();
			if (!$fbsession) {
				// If no FB session is found, just redirect
				return false;
			}
			// Facebook API uses strict error handling
			try {
				// Attempt to get uid and "me" data
				$uid = $fb->getUser();
				$me = $fb->api('/me');
			} catch (FacebookApiException $e) {
				return false;
			}

			// If we're logged in via facebook, let's check /me for updates
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
		$CFG = Load::Config();
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