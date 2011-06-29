<?php

global $CFG;
if (empty($CFG['twitter_key']) || empty($CFG['twitter_secret'])) { return; }

$hooks = Load::Hooks();
$hooks->registerMetadata('user',array('twitter_uid','icon_url'));
$hooks->registerContent('user',array('twitter_token'));

$hooks->registerUserAuth('twitter','twitter','oauth');
$hooks->registerEvent('login_choices','__twitter_oauth_link',0,0);

/* Oauth-Based Facebook Authentication */

function __twitter_oauth_link() {
	global $CFG;
	return '<a class="twitter-login-button" href="'.$CFG['wwwroot'].'/login/twitter/"><img src="'.$CFG['wwwroot'].'/plugins/twitter/images/sign-in-with-twitter.png" alt="Sign in with Twitter" style="border: 0px;" width="151" height="22" /></a>';
}