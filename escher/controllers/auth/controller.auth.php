<?php

class Controller_auth extends Controller {
	
	function action_login($args) {
		$session = Load::Session();
		$hooks = Load::Hooks();
		$session->remember_current_request = FALSE;
		if (Load::User()) { $this->postLoginRedirect(); }

		if(!empty($args)) {
			$userauth = Load::UserAuth($args[0]);
			if(!empty($userauth) && $userauth->authenticate()) {
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
				$user = Load::Model('user',array('username'=>$input->post['username']));
				if (!empty($user)) { $userauth = $user->getUserAuth(); }
				if (!empty($userauth) && $userauth->login($input->post['username'],@$input->post['password'])) {
					$session->regenerate();
					$_SESSION['user_id'] = $user->user_id;
					if (!empty($input->post['persist'])) {
						$_SESSION['persist'] = 1;
					}
					$session->updateCookie();
					$hooks->runEvent('login_success');
					$this->postLoginRedirect();
				} else {
					$this->headers->addNotification('Invalid username or password.','error');
					$hooks->runEvent('login_failure');
				}
			}
		}
		return true;
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
		$session->remember_current_request = FALSE;
		$session->updateCookie();
		$session->setFlash('logout_complete',TRUE);
		$this->headers->redirect();
	}
	
	function action_signup($args) {
		$headers = Load::Headers();
		if (Load::User()) { $headers->redirect(); }
		$CFG = Load::Config();
		$input = Load::Input();
		$UI = Load::UI();
		$this->data['post'] = $data = $input->post;
		$error = FALSE;
		if (!empty($input->post)) {
			$hooks = Load::Hooks();
			if (empty($data['username'])) {
				//$headers->addNotification('You must specify a username.','error');
				$UI->setInputStatus('username','error','Please choose a username');
				$error = TRUE;
			} elseif (in_array(strtolower($data['username']), $CFG['reserved_usernames'])
					|| in_array($data['username'], $CFG['reserved_usernames'])) {
				//$headers->addNotification('You have selected an invalid username.','error');
				$UI->setInputStatus('username','error','Invalid username');
				$error = TRUE;
			} elseif ($user = Load::User(array('username'=>$data['username']))) {
				//$headers->addNotification('The username you have selected is already in use.
				//	Please try a different username.','error');
				$UI->setInputStatus('username','error','Not available');
				$error = TRUE;
			}
			if (empty($data['email'])) {
				//$headers->addNotification('You must specify an email address.','error');
				$UI->setInputStatus('email','error','Email required');
				$error = TRUE;
			} elseif ($user = Load::User(array('email'=>$data['email']))) {
				//$headers->addNotification('The email you provided is already in use.','error');
				$UI->setInputStatus('email','error','This email is already registered');
				$error = TRUE;
			}
			if (empty($data['password'])) {
				//$headers->addNotification('Please select a password.','error');
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
				$headers->addNotification('You must agree to the terms of service to register for this website.','error');
				$error = TRUE;
			}

			if (!$error) {
				$vars = $input->post;
				if (file_exists('terms.txt')) {
					$vars['agreed_terms'] = md5_file('terms.txt');
				}
				$userauth = Load::Helper('userauth',$CFG['userauth']['default']['type'],$CFG['userauth']['default']);
				$userauth->register($input->post['username'],$input->post['password'],$vars);
				$headers->addNotification('Registration was successful.  You may now login.');
				$headers->redirect('~/');
			}
		}
	}
	
	function action_lockout($args) {
		$lockout = Load::Lockout();
		$USER = Load::User();
		$lockout->lock($args,$USER);
	}

	function postLoginRedirect() {
		$continue = $this->input->get('continue');
		if (!empty($continue)
			&& is_array($_SESSION['post_login_urls'])
			&& in_array($continue,$_SESSION['post_login_urls'])
		) {
			unset($_SESSION['post_login_urls'][array_search($continue,
				$_SESSION['post_login_urls'])]);
			$this->headers->redirect($continue);
		} else {
			$this->headers->redirect();
		}
	}
}