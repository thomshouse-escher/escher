<?php Load::HelperClass('userauth'); Load::lib(array('facebook','facebook.php'));

class Plugin_facebook_Helper_userauth_connect extends Helper_userauth {
	function authenticate() {
		$headers = Load::Headers();

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
			$_SESSION['user_id'] = $user->user_id;
			return true;
		}
		
		// Setup registration vars (username, fullname, etc.)
		$vars = $this->registrationVars($me);
		
		// Load the fbconnect userauth, register and redirect
		if ($user = $this->register($vars['username'],'',$vars)) {
			$_SESSION['user_id'] = $user->user_id;
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
		// We must know the facebook uid to register a facebook user
		if (!isset($vars['facebook_uid'])) {
			return false;
		}

		// Assign vars to the user and save
		$vars['username'] = $username;
		$vars['password'] = md5($username.NOW);
		$vars['user_auth'] = 'facebook';
		$user = Load::Model('user');
		return $user->register($vars);
	}

	function onLogin() {
		$USER = Load::User();
		if(empty($USER->facebook_uid)) { return; }

		// Let's track changes so we only save the model once!
		$doSave = FALSE;

		if ($USER->user_auth=='facebook') {
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
			if ($USER->display_name==$USER->facebook_display_name) {
				$USER->display_name = $USER->facebook_display_name = $this->formatName($me);
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
		$CFG = Load::CFG();
		$vars = array();
		$vars['display_name'] = $vars['facebook_display_name'] = $this->formatName($me);
		// If user has a facebook username and it doesn't exist locally or it is not a reserved username, let them have it
		if (!empty($me['username']) && !Load::User(array('username'=>$me['username'])) && 
			!in_array(strtolower($me['username']), $CFG['reserved_usernames']) && 
			!in_array($me['username'], $CFG['reserved_usernames'])
		){
			$vars['username'] = $me['username'];
		} else {
			// Otherwise give them something that should be unique based on their uid
			$vars['username'] = 'facebook.com/'.$me['id'];
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
