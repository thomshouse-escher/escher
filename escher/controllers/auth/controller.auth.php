<?php

class Controller_auth extends Controller {
	
	function action_login($args) {
		$hooks = Load::Hooks();
		if (Load::User()) { $this->postLoginRedirect(); }

		if(!empty($args)) {
			$userauth = Load::UserAuth($args[0]);
			if(!empty($userauth) && $user = $userauth->authenticate()) {
				$_SESSION['user_id'] = $user->user_id;
				if (strtotime($user->created_at)==NOW) {
					$hooks->runEvent('register_success');
				}
				$hooks->runEvent('authenticate_success');
				$this->postLoginRedirect();
			} else {
				$this->headers->addNotification('Invalid authentication request.','error');
				$hooks->runEvent('authenticate_failure');
				$this->headers->redirect();
			}
		}
		
		$input = Load::Input();
		if (!empty($input->get)) {
			$this->headers->addMeta('robots','noindex');
		}
		if (!empty($input->post)) {
			if (!empty($input->post['username'])) {
                $loginField = filter_var($input->post['username'],FILTER_VALIDATE_EMAIL)
                    ? 'email'
                    : 'username';
				$user = Load::Model('user',array($loginField=>$input->post['username']));
				if (!empty($user)) { $userauth = $user->getUserAuth(); }
				if (!empty($userauth) && $userauth->login($input->post['username'],@$input->post['password'])) {
					$this->session->regenerate();
					$_SESSION['user_id'] = $user->user_id;
					if (!empty($input->post['persist'])) {
						$_SESSION['persist'] = 1;
					}
					$this->session->updateCookie();
					$hooks->runEvent('login_success');
					$this->postLoginRedirect();
				} else {
					$this->headers->addNotification('Invalid username or password.','error');
					$hooks->runEvent('login_failure');
				}
			}
		} elseif (!$this->input->get('continue')
			&& ($referer = $this->headers->getReferer())
			&& $referer != '/'
		) {
			$this->headers->redirect('@auth>login/?continue='.$referer);
		}
		return true;
	}

	function postLoginRedirect() {
		if ($continue = $this->input->get('continue')) {
			$this->headers->redirect($continue);
		} else {
			$this->headers->redirect('~/');
		}
	}

	function action_logout($args) {
		if ($user = Load::User()) {
			$ua = $user->getUserAuth();
			$ua->deauthenticate();
		}
		$session = Load::Session();
		unset($_SESSION['user_id']);
		unset($_SESSION['persist']);
		$session->regenerate();
		$session->updateCookie();
		$session->setFlash('logout_complete',TRUE);
		$this->headers->redirect();
	}
	
	function action_signup($args) {
		$hooks = Load::Hooks();
		if (Load::User()) { $this->headers->redirect(); }
		$CFG = Load::Config();
		$input = Load::Input();
		$UI = Load::UI();
		$this->data['post'] = $data = $input->post;
		$error = FALSE;
		if (!empty($input->post)) {
			$userauth = Load::Userauth();
			if (empty($data['username'])) {
				$this->headers->addNotification('You must specify a username.','error');
				$UI->setInputStatus('username','error','Please choose a username');
				$error = TRUE;
			} elseif (!$userauth->usernameIsAvailable($data['username'])) {
				$this->headers->addNotification('The username you have selected is already in use.
				//	Please try a different username.','error');
				$UI->setInputStatus('username','error','Not available');
				$error = TRUE;
			}
			if (empty($data['email'])) {
				$this->headers->addNotification('You must specify an email address.','error');
				$UI->setInputStatus('email','error','Email required');
				$error = TRUE;
			} elseif ($user = Load::User(array('email'=>$data['email']))) {
				$this->headers->addNotification('The email you provided is already in use.','error');
				$UI->setInputStatus('email','error','This email is already registered');
				$error = TRUE;
			}
			if (empty($data['password'])) {
				$this->headers->addNotification('Please select a password.','error');
				$UI->setInputStatus('password','error','Please choose a password');
				$error = TRUE;
			}
			
			$captcha = $hooks->runEvent('captcha_verify');
			if (!empty($captcha)) {
				foreach($captcha as $c) {
					if ($c===FALSE) { $error = TRUE; }
				}
			}

			if (empty($data['agree_terms'])) {
				$this->headers->addNotification('You must agree to the terms of service to register for this website.','error');
				$error = TRUE;
			}

			if (!$error) {
				$vars = $input->post;
				if (file_exists('terms.txt')) {
					$vars['agreed_terms'] = md5_file('terms.txt');
				}
				if ($user = $userauth->register($input->post['username'],
					$input->post['password'],$vars)
				) {
					$_SESSION['user_id'] = $user->user_id;
					$hooks->runEvent('register_success');
					$this->headers->addNotification('Registration was successful.','success');
					$this->headers->redirect('~/');
				} else {
					$hooks->runEvent('register_failure');
					$this->headers->addNotification('Registration failed.','error');
				}
			}
		}
	}
	
	function action_lockout($args) {
		$lockout = Load::Lockout();
		$USER = Load::User();
		$lockout->lock($args,$USER);
	}
}