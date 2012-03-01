<?php Load::HelperClass('userauth'); Load::lib(array('twitter','twitteroauth.php'));

class Plugin_twitter_Helper_userauth_oauth extends Helper_userauth {
	protected $me = NULL;

	function authenticate() {
		if(empty($_REQUEST['oauth_verifier']) || empty($_REQUEST['oauth_token'])) {
			return $this->oauth_request();
		} else {
			return $this->oauth_verify();
		}
	}

	protected function oauth_request() {
		$headers = Load::Headers();
		
		$CFG = Load::Config();

		/* Build TwitterOAuth object with client credentials. */
		$connection = new TwitterOAuth($CFG['twitter_key'],$CFG['twitter_secret']);
		 
		/* Get temporary credentials. */
		$request_token = $connection->getRequestToken($CFG['wwwroot'].'/login/twitter/');

		/* Save temporary credentials to session. */
		$_SESSION['tw_oauth_token'] = $token = $request_token['oauth_token'];
		$_SESSION['tw_oauth_token_secret'] = $request_token['oauth_token_secret'];
		 
		/* If last connection failed don't display authorization link. */
		if ($connection->http_code==200) {
			/* Build authorize URL and redirect user to Twitter. */
			$url = $connection->getAuthorizeURL($token);
			$headers->redirect($url);
		}
		return false;
	}
	
	protected function oauth_verify() {
		$headers = Load::Headers();

		$CFG = Load::Config();

		/* If the oauth_token is old redirect to the connect page. */
		if ($_SESSION['tw_oauth_token'] !== $_REQUEST['oauth_token']) {
			$headers->redirect($CFG['wwwroot'].'/login/twitter/');
		}

		/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
		$connection = new TwitterOAuth($CFG['twitter_key'],$CFG['twitter_secret'], $_SESSION['tw_oauth_token'], $_SESSION['tw_oauth_token_secret']);

		/* Request access tokens from twitter */
		$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

		/* Remove no longer needed request tokens */
		unset($_SESSION['tw_oauth_token']);
		unset($_SESSION['tw_oauth_token_secret']);

		/* If HTTP response is 200 continue otherwise send to connect page to retry */
		if ($connection->http_code != 200) {
			return false;
		}

		$this->me = $me = (array)$connection->get('account/verify_credentials');

		// If there is a local user with this facebook uid, log them in and redirect
		if ($user = Load::User(array('twitter_uid'=>$me['id']))) {
			$_SESSION['user_id'] = $user->user_id;
			return true;
		}
		
		// Setup registration vars (username, fullname, etc.)
		$vars = $this->registrationVars($me);
		$vars['twitter_token'] = $access_token;

		// Load the facebook (oauth) userauth, register, and redirect
		$userauth = Load::Helper(array('twitter','userauth'),'oauth');
		if ($user = $userauth->register($vars['username'],'',$vars)) {
			$_SESSION['user_id'] = $user->user_id;
			return true;
		}
		return false;
	}
	
	function register($username,$password=NULL,$vars=array()) {
		// We must know the twitter uid and token to register a twitter user
		if (!isset($vars['twitter_uid']) || !isset($vars['twitter_token'])) {
			return false;
		}
		
		// Assign vars to the user and save
		$vars['username'] = $username;
		$vars['password'] = md5($username.NOW);
		$vars['user_auth'] = 'twitter';
		$user = Load::Model('user');
		return $user->register($vars);
	}

	protected function registrationVars($me) {
		$CFG = Load::CFG();
		$vars = array();
		$vars['display_name'] = $vars['twitter_display_name'] = $me['name'];
		// If user's twitter username is available locally, use it
		if (!empty($me['username']) && $this->usernameIsAvailable($me['username'])) {
			$vars['username'] = $me['screen_name'];
		} else {
			// Otherwise give them something that should be unique based on their uid
			$vars['username'] = 'twitter.com/'.$me['id'];
		}
		$vars['twitter_uid'] = $me['id'];
		return $vars;
	}

	function onLogin() {
		$USER = Load::User();
		if(empty($USER->twitter_uid)) { return; }

		// Let's track changes so we only save the model once!
		$doSave = FALSE;

		// If we have a twitter token, check credentials for updates
		if (!empty($USER->twitter_token)) {
			$CFG = Load::CFG();
			if (is_null($this->me)) {
				$connection = new TwitterOAuth($CFG['twitter_key'],$CFG['twitter_secret'], 
					$USER->twitter_token['oauth_token'], 
					$USER->twitter_token['oauth_token_secret']);
				$me = (array)$connection->get('account/verify_credentials');
			} else {
				$me = $this->me;
			}

			if(!empty($me['screen_name']) && @$USER->twitter_username!=$me['screen_name']) {
				$USER->twitter_username = $me['screen_name'];
				$doSave = TRUE;
			}
			if ($USER->display_name==$USER->twitter_display_name) {
				$USER->display_name = $USER->twitter_display_name = $me['name'];
				$doSave = TRUE;
			}

			// If avatar is coming from Twitter or doesn't exist, grab it
			if (empty($USER->avatar_source) || $USER->avatar_source=='twitter') {
				$USER->avatar_url = $me['profile_image_url'];
				$USER->avatar_source = 'twitter';
				$doSave = TRUE;
			}
		}

		if ($doSave) {
			$USER->save();
		}

	}
}
