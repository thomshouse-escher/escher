<?php Load::HelperClass('userauth'); Load::lib(array('twitter','twitteroauth.php'));

class Plugin_twitter_Helper_userauth_oauth extends Helper_userauth {
	function authenticate() {
		if(empty($_REQUEST['oauth_verifier']) || empty($_REQUEST['oauth_token'])) {
			$this->oauth_request();
		} else {
			$this->oauth_verify();
		}
	}

	protected function oauth_request() {
		$headers = Load::Headers();
		
		global $CFG;

		/* Build TwitterOAuth object with client credentials. */
		$connection = new TwitterOAuth($CFG['twitter_key'],$CFG['twitter_secret']);
		 
		/* Get temporary credentials. */
		$request_token = $connection->getRequestToken($CFG['wwwroot'].'/login/twitter/');

		/* Save temporary credentials to session. */
		$_SESSION['tw_oauth_token'] = $token = $request_token['oauth_token'];
		$_SESSION['tw_oauth_token_secret'] = $request_token['oauth_token_secret'];
		 
		/* If last connection failed don't display authorization link. */
		switch ($connection->http_code) {
			case 200:
				/* Build authorize URL and redirect user to Twitter. */
				$url = $connection->getAuthorizeURL($token);
				$headers->redirect($url);
				break;
			default:
				/* (Don't) Show notification if something went wrong. */
				$headers->redirect();
		}
	}
	
	protected function oauth_verify() {
		$headers = Load::Headers();

		/* If the oauth_token is old redirect to the connect page. */
		if ($_SESSION['tw_oauth_token'] !== $_REQUEST['oauth_token']) {
			$headers->redirect($CFG['wwwroot'].'/login/twitter/');
		}

		/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
		$connection = new TwitterOAuth($CFG['twitter_key'],$CFG['twitter_secret'], $_SESSION['tw_oauth_token'], $_SESSION['tw_oauth_token_secret']);

		/* Request access tokens from twitter */
		$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

		/* Remove no longer needed request tokens */
		//unset($_SESSION['tw_oauth_token']);
		//unset($_SESSION['tw_oauth_token_secret']);

		/* If HTTP response is 200 continue otherwise send to connect page to retry */
		if ($connection->http_code != 200) {
			$headers->redirect();
		}

		$me = (array)$connection->get('account/verify_credentials');

		// If there is a local user with this facebook uid, log them in and redirect
		if ($user = Load::User(array('twitter_uid'=>$me['id']))) {
			$_SESSION['user_id'] = $user->id;
			$user->assignVars($this->loginVars($me));
			$user->save();
			$headers->redirect();
		}
		
		// Setup registration vars (username, fullname, etc.)
		$vars = $this->registrationVars($me);
		$vars['twitter_token'] = $access_token;

		// Load the facebook (oauth) userauth, register, and redirect
		$userauth = Load::Helper('userauth',array('twitter','oauth'));
		if ($user = $userauth->register($vars['username'],'',$vars)) {
			$_SESSION['user_id'] = $user->id;
		}
		$headers->redirect();
	}
	
	function register($username,$password=NULL,$vars=array()) {
		// We must know the twitter uid and token to register a twitter user
		if (!isset($vars['twitter_uid']) || !isset($vars['twitter_token'])) {
			return false;
		}
		// Password will not get used, fill it with noise
		$password = sha1($username.time());
		
		// Assign vars to the user and save
		$user = Load::Model('user');
		$vars['username'] = $username;
		$vars['password'] = $password;
		$vars['auth'] = 'twitter';
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
		if (!empty($me['screen_name']) && !Load::User(array('username'=>$me['screen_name']))) {
			// If user has a facebook username and it's not already taken in the system, let them have it
			$vars['username'] = $me['screen_name'];
		} else {
			// Otherwise give them something that should be unique based on their uid
			$vars['username'] = '@twitter:'.$me['id'];
		}
		$vars['twitter_uid'] = $me['id'];
		return $vars;
	}

	protected function loginVars($me) {
		$vars = array('full_name' => $me['name'],'icon_url' => $me['profile_image_url']);
		if(!empty($me['screen_name'])) { $vars['twitter_username'] = $me['screen_name']; }
		return $vars;
	}
}