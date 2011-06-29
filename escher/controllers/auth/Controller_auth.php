<?php

class Controller_auth extends Controller {
	
	function action_login($args) {
		$session = Load::Session();
		$headers = Load::Headers();
		$session->remember_current_request = FALSE;
		if (Load::User()) { $headers->redirect(); }

		if(!empty($args)) {
			$userauth = Load::UserAuth($args[0]);
			if(!$userauth || !$userauth->authenticate()) {
				$headers->addNotification('Invalid authentication request.','error');
			}
			$headers->redirect('./');
		}
		
		$input = Load::Input();
		if (!empty($input->post)) {
			if (!empty($input->post['username'])) {
				$user = Load::Model('user',array('username'=>$input->post['username']));
				if ($user) { $userauth = $user->getUserAuth(); }
				if (!$userauth || !$userauth->login($input->post['username'],@$input->post['password'])) {
					$headers->addNotification('Invalid username or password.','error');
				} else {
					$session->regenerate();
					$_SESSION['user_id'] = $user->id;
					if (!empty($args['persist'])) {
						$_SESSION['persist'] = 1;
					}
					$session->updateCookie();
					$headers->redirect();
				}
			}
		}
		$this->display('login');
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
		$headers = Load::Headers();
		$headers->redirect();
	}
	
	function action_register($args) {
		global $CFG;
		$headers = Load::Headers();
		$input = Load::Input();
		$data = $input->post;
		$errors = array();
		if (!empty($input->post)) {
			if (in_array(strtolower($input->post['username']),$CFG['reserved_usernames']) ||
					in_array($input->post['username'],$CFG['reserved_usernames'])) {
				$headers->addNotification('You have selected an invalid username.','error');
				unset($data['username']);
				$errors[] = 'username';
			} elseif ($user = Load::User(array('username'=>$input->post['username']))) {
				$headers->addNotification('The username you have selected is already in use.
					Please try a different username.','error');
				unset($data['username']);
				$errors[] = 'username';
			}
			if ($user = Load::User(array('email'=>$input->post['email']))) {
				$headers->addNotification('The email you provided is already in use.','error');
				unset($data['email']);
				$errors[] = 'email';
			}
			if (empty($data['password'])) {
				$headers->addNotification('Please provide a password.','error');
				$errors[] = 'password';
				$errors[] = 'password2';
			} else if (isset($data['password2']) && $data['password']!=$data['password2']) {
				$headers->addNotification('Passwords do not match.','error');
				$errors[] = 'password';
				$errors[] = 'password2';
			}
			if (empty($errors)) {
				$userauth = Load::Helper('userauth',$CFG['userauth']['default']['type'],$CFG['userauth']['default']);
				$userauth->register($input->post['username'],$input->post['password'],$input->post);
				$headers->addNotification('Registration was successful.  You may now login.');
				$headers->redirect('~/');
			}
		}
		$this->data['errors'] = $errors;
	}
	
	function action_lockout($args) {
		$lockout = Load::Lockout();
		$USER = Load::User();
		$lockout->lock($args,$USER);
	}
}