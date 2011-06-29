<?php Load::HelperClass('userauth'); Load::lib(array('facebook','facebook.php'));

class Plugin_facebook_Helper_userauth_connect extends Helper_userauth {
	function authenticate() {
		$headers = Load::Headers();

		// Load FB API and session
		$fb = loadFacebookAPI();
		$fbsession = $fb->getSession();
		if (!$fbsession) {
			// If no FB session is found, just redirect
			$headers->redirect();
		}
		// Facebook API uses strict error handling
		try {
			// Attempt to get uid and "me" data
			$uid = $fb->getUser();
			$me = $fb->api('/me');
		} catch (FacebookApiException $e) {
			$headers->redirect();
		}

		// If there is a local user with this facebook uid, log them in and redirect
		if ($user = Load::User(array('facebook_uid'=>$uid))) {
			$_SESSION['user_id'] = $user->id;
			$user->assignVars($this->loginVars($me));
			$user->save();
			$headers->redirect();
		}
		
		// Setup registration vars (username, fullname, etc.)
		$vars = $this->registrationVars($me);
		
		// Load the fbconnect userauth, register and redirect
		if ($user = $this->register($vars['username'],'',$vars)) {
			$_SESSION['user_id'] = $user->id;
		}
		$headers->redirect();
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