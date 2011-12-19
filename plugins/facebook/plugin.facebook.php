<?php

class Plugin_facebook extends Plugin {
	protected $events = array(
		'authenticate_success' => 'onLogin',
		'login_success' => 'onLogin',
	);
	protected $modelMetadata = array(
		'user' => array('facebook_uid','facebook_username','facebook_full_name'),
	);

	function __construct() {
		$CFG = Load::Config();
		if (empty($CFG['facebook_appId']) || empty($CFG['facebook_secret'])) { return; }
		if (!empty($CFG['facebook_auth']) && $CFG['facebook_auth']=='connect') {
			// If you are using the JS "connect-style" auth, load those hooks
			$this->userAuth = array('facebook'=>'connect');
			$this->events['html_body_start'] = 'jsInit';
			$this->events['auth_choices'] = 'jsLink';
		} else {
			// Otherwise, load the hooks for Oauth
			$this->userAuth = array('facebook'=>'oauth');
			$this->events['auth_choices'] = 'oauthLink';
		}
	}

	function onLogin() {
		$auth = Load::UserAuth('facebook');
		$auth->onLogin();
	}

	/* Oauth-Based Facebook Authentication */
	function oauthLink() {
		$CFG = Load::Config();
		$browser = Load::Helper('useragent','default');
		return '<a class="facebook-login-button" href="https://www.facebook.com/dialog/oauth?client_id='.$CFG['facebook_appId'].'&amp;redirect_uri='.urlencode($CFG['wwwroot'].'/login/facebook/').($browser->match('mobile') ? '&amp;display=touch' : '').'"><img src="'.$CFG['wwwroot'].'/plugins/facebook/images/sign-in-with-facebook.png" alt="Sign in with Facebook" style="border: 0px;" width="151" height="22" /></a>';
	}

	/* JS-Based Facebook "Connect" Login */
	function jsInit() {
		$CFG = Load::Config();
		$USER = Load::User();
		return "<div id=\"fb-root\"></div>
		<script>
		window.fbAsyncInit = function() {
			FB.init({appId: '{$CFG['facebook_appId']}', status: true, cookie: true,
				xfbml: true});".(empty($USER) ? "
			FB.Event.subscribe('auth.login', function(response) {
				window.location = '{$CFG['wwwroot']}/login/facebook/';" : '')."
			});
		};
		(function() {
			var e = document.createElement('script'); e.async = true;
			e.src = document.location.protocol +
				'//connect.facebook.net/en_US/all.js';
			document.getElementById('fb-root').appendChild(e);
		}());
		</script>";
	}

	function jsLink() {
		Load::lib(array('facebook','facebook.php'));
		$fb = loadFacebookAPI();
		return '<a class="facebook-login-button" href="'.$fb->getLoginUrl().'"><img src="'.$CFG['wwwroot'].'/plugins/facebook/images/connect-with-facebook.png" alt="Connect with Facebook" style="border: 0px;" width="151" height="22" /></a>';
	}

	/* FBML-style button if you so desire */
	function jsButton() {
		return '<fb:login-button>Login with Facebook</fb:login-button>';
	}
}