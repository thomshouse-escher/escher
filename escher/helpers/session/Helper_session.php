<?php

abstract class Helper_session extends Helper {
	public $remember_current_request = TRUE;
	protected $useCustomHandler = TRUE;
	protected $cookieName = 'escher_session';
	protected $cookiePath = '/';
	protected $cookieDomain = NULL;
	protected $daysToPersist = 30;
	public $preserveFlash = FALSE;
	
	function __construct() {
		parent::__construct();
		$this->daysToPersist = round($this->daysToPersist);
		if (empty($this->cookiePath)) {
			$this->cookiePath = '/';
		}
		if (empty($this->cookieDomain)) {
			$this->cookieDomain = $_SERVER['HTTP_HOST'];
		}
		// If we're using a custom save handler, set it now:
		if ($this->useCustomHandler) {
			session_set_save_handler(
				array(&$this,'openHandler'),
				array(&$this,'closeHandler'),
				array(&$this,'readHandler'),
				array(&$this,'writeHandler'),
				array(&$this,'destroyHandler'),
				array(&$this,'garbageHandler')
			);
		}
		// Set the session name, gc, and start!
		session_name($this->cookieName);
		ini_set('session.gc_maxlifetime',$this->daysToPersist*24*60*60);
		session_start();
		/* Note: if you really are using PHP's default sessions (really?),
		you may experience unexpected session loss.  This is likely
		not a bug in Escher, but a result of vendor-specific behavior
		of your operating system.  Debian is known to override PHP's default
		session garbage collection. */

		// We need to register the queue_shutdown function here
		register_shutdown_function(array($this,'shutdown'));
		// Create the cookie w/ proper values
		$this->updateCookie();

		if(!empty($_SESSION['user_id']) && $user = Load::User()) {
			$userauth = $user->getUserAuth();
			if (!$userauth->reauthenticate()) {
				unset($_SESSION['user_id']);
				unset($_SESSION['persist']);
				$this->remember_current_request = FALSE;
				$this->updateCookie();
				$this->setFlash('logout_complete',TRUE);
				$headers = Load::Headers();
				$headers->redirect();
			}
		}
	}

	function updateCookie() {				
		// We can't check for session persistence until we have started the session
		if ($this->daysToPersist==0 || empty($_SESSION['persist'])) {
			$session_timestamp = 0;
		}
		// If daysToPersist is nonzero, generate our timestamp
		else {
			$session_timestamp = NOW + $this->daysToPersist*60*60*24;
		}

		// Now that we have our timestamp, we can fully set up the session cookie
		setcookie($this->cookieName,session_id(),$session_timestamp,$this->cookiePath,$this->cookieDomain);
	}

	function regenerate() {
		session_regenerate_id(TRUE);
	}
	
	function setFlash($name,$value,$array=FALSE) {
		if (!is_string($name)) {
			return false;
		}
		if ($array) {
			if (isset($_SESSION['_FLASH'][$name])) {
				$_SESSION['_FLASH'][$name] = array_merge(
					(array)$_SESSION['_FLASH'][$name],
					array($value));
			} else {
				$_SESSION['_FLASH'][$name] = array($value);
			}
		} else {
				$_SESSION['_FLASH'][$name] = $value;
		}
		return true;
	}
	
	function getFlash($name,$keep=FALSE) {
		$result = @$_SESSION['_FLASH'][$name];
		if (!$keep) {
			unset($_SESSION['_FLASH'][$name]);
		}
		return $result;
	}
	
	protected function pruneFlash() {
		$_SESSION['_FLASH'] = array();
	}
		
	function shutdown() {
		if (!$this->preserveFlash) {
			$this->pruneFlash();
		}
		if ($this->remember_current_request && empty($_POST)) {
			global $CFG;
			$router = Load::Router();
			$lastreq = $CFG['wwwroot'].$router->path;
			if (!empty($_SERVER['QUERY_STRING'])) {
				$lastreq .= '?'.$_SERVER['QUERY_STRING'];
			}
			$this->setFlash('last_request_url',$lastreq);
		}
		session_write_close();
	}

	function openHandler($save_path,$session_name) {}
	function closeHandler() {}
	function readHandler($id) {}
	function writeHandler($id,$data) {}
	function destroyHandler($id) {}
	function garbageHandler($lifetime) {}
}