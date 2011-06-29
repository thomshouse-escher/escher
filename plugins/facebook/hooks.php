<?php

global $CFG;
if (empty($CFG['facebook_appId']) || empty($CFG['facebook_secret'])) { return; }

$hooks = Load::Hooks();
$hooks->registerMetadata('user',array('facebook_uid','icon_url'));

if (!empty($CFG['facebook_auth']) && $CFG['facebook_auth']=='connect') {
	// If you MUST use the JS-based "connect" logins, here's what you need...
	$hooks->registerUserAuth('facebook','facebook','connect');
	$hooks->registerEvent('html_body_start','__facebook_js_init',0,0);
	$hooks->registerEvent('login_choices','__facebook_js_link',0,0);
} else {
	// Otherwise, load the hooks for Oauth
	$hooks->registerUserAuth('facebook','facebook','oauth');
	$hooks->registerEvent('login_choices','__facebook_oauth_link',0,0);
}



/* Oauth-Based Facebook Authentication */

function __facebook_oauth_link() {
	global $CFG;
	$browser = Load::Helper('useragent','default');
	return '<a class="facebook-login-button" href="https://www.facebook.com/dialog/oauth?client_id='.$CFG['facebook_appId'].'&amp;redirect_uri='.urlencode($CFG['wwwroot'].'/login/facebook/').($browser->match('mobile') ? '&display=touch' : '').'"><img src="'.$CFG['wwwroot'].'/plugins/facebook/images/sign-in-with-facebook.png" alt="Sign in with Facebook" style="border: 0px;" width="151" height="22" /></a>';
}



/* JS-Based Facebook "Connect" Login */

function __facebook_js_init() {
	global $CFG;
	return "<div id=\"fb-root\"></div>
	<script>
	window.fbAsyncInit = function() {
		FB.init({appId: '{$CFG['facebook_appId']}', status: true, cookie: true,
			xfbml: true});
		FB.Event.subscribe('auth.login', function(response) {
			window.location = '{$CFG['wwwroot']}/fb_auth_login/';
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

function __facebook_js_link() {
	Load::lib(array('facebook','facebook.php'));
	$fb = loadFacebookAPI();
	return '<a class="facebook-login-button" href="'.$fb->getLoginUrl().'"><img src="'.$CFG['wwwroot'].'/plugins/facebook/images/connect-with-facebook.png" alt="Connect with Facebook" style="border: 0px;" width="151" height="22" /></a>';
}

/* FBML-style button if you so desire */
function __facebook_js_button() {
	return '<fb:login-button>Login with Facebook</fb:login-button>';
}