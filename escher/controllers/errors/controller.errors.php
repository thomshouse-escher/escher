<?php

class Controller_errors extends Controller {
	function execute($args=NULL) {
		// Sanitize $args
		if (is_null($args)) {
			$args = $this->args;
		}
		$args = (array)$args;
		if (isset($this->router->action)) {
			$error = $this->router->action;
		} else {
			$error = array_shift($args);
		}
		if (empty($error)) {
			$error = '404';
		}
		$this->headers->addMeta('robots','noindex');
		if ($error=='401') { $this->unauthorized($args); }
		$this->display($error,$args);
	}

	function unauthorized($args) {
		// Make sure a logout is not occuring-- if so send them back to the root.
		$session = Load::Session();
		if ($session->getFlash('logout_complete')) {
			$this->headers->redirect('~/');
		}
		
		// Load the config
		$CFG = Load::CFG();
		
		// Build the redirect string
		$router = Load::Router();
		$redirect_back = $router->getCurrentPath(FALSE,TRUE);

		if (!isset($_SESSION['post_login_urls'])) {
			$_SESSION['post_login_urls'] = array();
		}
		$_SESSION['post_login_urls'][] = $redirect_back;
		
		// Check the login path. If the login path is not set in the config, default to static login.
		$login = !empty($CFG['login_url']) ? $login = $CFG['login_url'] : '/login/';
		
		// If query string use ampersand, if not use question mark.
		$qstr = (strpos($login,'?')===FALSE) ? '?' : '&';
		
		// Redirect to login with redirect set.
		$this->headers->redirect($login.$qstr.'continue='.urlencode($redirect_back));
	}
}