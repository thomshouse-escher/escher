<?php Load::HelperClass('userauth');

class Plugin_google_Helper_userauth extends Helper_userauth {
	protected $googleUser;

	function authenticate() {
		// Get the Oauth token
		if (!$token = $this->getToken()) { return FALSE; }

		// Get the user info from the Google API
		$gu = $this->getUserData($token);

		// Get the user if it exists
		if ($user = $this->findUser($gu,$token)) {
			$_SESSION['user_id'] = $user->user_id;
			return $user;
		}

		// Otherwise, attempt to create the user
		return $this->createUser($gu,$token);
	}

	function getToken() {
		// Authorize using Oauth
		$oauth = Load::Helper(array('google','oauth'));
		if ($response = $oauth->authorize()) {
			return $response->access_token;
		}
		return FALSE;
	}

	function getUserData($token) {
		if (!empty($this->googleUser)) {
			return $this->googleUser;
		} else {
			$curl = curl_init('https://www.googleapis.com/oauth2/v1/userinfo?access_token='.$token);
			curl_setopt_array($curl,array(
				CURLOPT_FOLLOWLOCATION => TRUE,
				CURLOPT_AUTOREFERER => TRUE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_SSL_VERIFYPEER => FALSE,
			));
			$response = curl_exec($curl);
			return $this->googleUser = json_decode($response);
		}
	}

	function findUser($data,$token=NULL) {
		if ($user = Load::Model('user',array(
			'user_auth' => 'google',
			'google_id' => $data->id
		))) {
			if (!empty($token)) {
				$user->google_token = $token;
				$user->save();
			}
			return $user;
		}
		return FALSE;
	}

	function createUser($data,$token) {
		// Start building the user array
		$id = $data->id;
		$login = substr('google.com/'.$id,0,35);
		$data->login = $login;
		$vars = array(
			'user_auth'     => 'google',
			'display_name'  => $data->name,
			'avatar_url'    => $data->picture,
			'avatar_source' => 'google',
			'google_id'     => $id,
			'google_name'   => $data->name,
			'google_login'  => $login,
			'google_token'  => $token,
		);
		if (!empty($data->login) && $this->usernameIsAvailable($login)) {
			$vars['username'] = $login;
		} else {
			// Otherwise give them something that should be unique based on their uid
			$vars['username'] = $login;
		}
		$user = Load::Model('user');
		return $user->register($vars);
	}

	function onLogin() {
		$user = Load::User();
		// Google id and token are required to proceed
		if(empty($user->google_id) || empty($user->google_token)) { return; }

		// Track changes so model is only saved if necessary
		$doSave = FALSE;

		// Get the user's Google data
		$gu = $this->getUserData($user->google_token);

		// Check to see if google name has changed
		if ($user->google_name!=$gu->name) {
			// Check if display_name and user_auth are google
			if ($user->user_auth=='google'
				&& $user->display_name==$user->google_name
			) {
				$user->display_name = $gu->name;
			}
			$user->google_name = $gu->name;
			$doSave = TRUE;
		}

		// Check if avatar has changed and google is the source
		if ($user->avatar_source=='google'
			&& $user->avatar_url!=$gu->picture
		) {
			$user->avatar_url = $gu->picture;
			$doSave = TRUE;
		}

		if ($doSave) {
			$user->save();
		}
	}
}