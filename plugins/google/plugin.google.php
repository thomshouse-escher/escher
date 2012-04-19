<?php

class Plugin_google extends Plugin {
	protected $events = array(
		'auth_choices' => 'createLink',
		'authenticate_success' => 'onLogin',
		'login_success' => 'onLogin',
	);
	protected $schemaFields = array(
		'user' => array(
			'google_id'    => 'resource',
			'google_name'  => 'string',
			'google_login' => 'resource',
			'google_token' => 'string',
		),
	);
	protected $userAuth = array('google' => 'default');

	function __construct() {
		$config = Load::Config();
		if (empty($config['google_clientId'])
			|| empty($config['google_secret'])
			|| !empty($config['google_noauth'])
		) {
			$this->events = array();
			$this->userAuth = array();
		}
	}
	
	function createLink() {
		$router = Load::Router();
		return '<a class="google-login-button" href="'.$router->getRootPath()
			.'/login/google/"><img src="'.$router->getRootPath()
			.'/plugins/google/images/sign-in-with-google.png" '
			.'alt="Sign in with Google" style="border: 0px;" '
			.'width="151" height="22" /></a>';
	}

	function onLogin() {
		$auth = Load::UserAuth('google');
		$auth->onLogin();
	}
}