<?php

class Plugin_twitter extends Plugin {
	protected $events = array(
		'auth_choices' => 'createLink',
		'authenticate_success' => 'onLogin',
		'login_success' => 'onLogin',
	);
	protected $schemaFields = array(
		'user' => array(
			'twitter_uid' => 'string',
			'twitter_token' => 'array',
		),
	);
	protected $userAuth = array('twitter' => 'oauth');

	function __construct() {
		$CFG = Load::Config();
		if (empty($CFG['twitter_key']) || empty($CFG['twitter_secret'])) {
			$this->events = array();
			$this->userAuth = array();
		}
	}
	
	function createLink() {
		$CFG = Load::Config();
		return '<a class="twitter-login-button" href="'.$CFG['wwwroot'].'/login/twitter/"><img src="'.$CFG['wwwroot'].'/plugins/twitter/images/sign-in-with-twitter.png" alt="Sign in with Twitter" style="border: 0px;" width="151" height="22" /></a>';
	}

	function onLogin() {
		$auth = Load::UserAuth('twitter');
		$auth->onLogin();
	}
}
