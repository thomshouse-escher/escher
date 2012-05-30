<?php

class Plugin_google_Helper_oauth extends Helper {
	protected $authURL  = 'https://accounts.google.com/o/oauth2/auth';
	protected $tokenURL = 'https://accounts.google.com/o/oauth2/token';
	protected $defaultScope = 'https://www.googleapis.com/auth/userinfo.profile';
	protected $clientId;
	protected $secret;

	function __construct($args=array()) {
		// Get id & secret from config
		$config = Load::Config();
		if (!empty($config['google_clientId'])
			&& !empty($config['google_secret'])
		) {
			$this->assignVars(array(
				'clientId' => $config['google_clientId'],
				'secret'   => $config['google_secret'],
			));
		}
		// Load the default helper
		parent::__construct($args);
	}

	function authorize($redirect=NULL,$scope=NULL) {
		$router = Load::Router();
		if (is_null($redirect)) {
			// Get a redirect if not provided
			$redirect = $router->getCurrentPath(TRUE,TRUE);
		} else {
			// Otherwise, resolve the redirect path
			$redirect = $router->resolvePath($redirect);
		}

		// Get scope if not provided
		if (is_null($scope)) {
			$config = Load::Config();
			if (isset($config['google_scopes'])) {
				$scope = $config['google_scopes'];
			}
		}
		// Clean up scope
		if (empty($scope)) {
			$scope = $this->defaultScope;
		}
		if (is_array($scope)) {
			$scope = '&scope='.implode(',',$scope);
		} else {
			$scope = "&scope=$scope";
		}
		// Upon first attempt, we must redirect to service provider
		$input = Load::Input();
		if (empty($input->get['code'])) {
			$headers = Load::Headers();
			$headers->redirect($this->authURL
				.'?client_id='.$this->clientId
				.'&redirect_uri='.urlencode($redirect)
				.'&response_type=code'
				.$scope
			,TRUE);
		}
		// Once we have the code from the service provider, get the token
		$curl = curl_init($this->tokenURL);
		curl_setopt_array($curl,array(
			CURLOPT_POST => TRUE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_AUTOREFERER => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_POSTFIELDS =>
				'code='.$input->get['code']
				.'&client_id='.$this->clientId
				.'&client_secret='.$this->secret
				.'&redirect_uri='.urlencode($redirect)
				.'&grant_type=authorization_code'
		));
		$response = curl_exec($curl);
		
		// Return the authorization response as an array
		$returns = json_decode($response);
		if (!empty($returns->error)) { return FALSE; }
		return $returns;
	}
}