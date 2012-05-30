<?php

class Helper_session extends Helper {
	protected $useCustomHandler = FALSE;
	protected $cookieName = 'escher_session';
	protected $cookiePath = '/';
	protected $cookieDomain = NULL;
	protected $daysToPersist = 30;
	protected $cookieExists;
	public $preserveFlash = FALSE;
	
	function __construct() {
		ob_start();
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

		// We need to register the shutdown function here
		register_shutdown_function(array($this,'close'));

		// Start the session if the session cookie exists
		if (array_key_exists($this->cookieName,$_COOKIE)) {
			$this->startSession();
			if(!empty($_SESSION['user_id']) && $user = Load::User()) {
				$userauth = $user->getUserAuth();
				if (!$userauth->reauthenticate()) {
					unset($_SESSION['user_id']);
					unset($_SESSION['persist']);
					$this->updateCookie();
					$this->setFlash('logout_complete',TRUE);
					$headers = Load::Headers();
					$headers->redirect();
				}
			}
		}
	}

	protected function startSession() {
		// Only run once per request
		if ($this->cookieExists) { return; }

		// Set the session name, gc, and start!
		session_name($this->cookieName);
		session_set_cookie_params(0,$this->cookiePath,$this->cookieDomain);
		ini_set('session.gc_maxlifetime',$this->daysToPersist*24*60*60);
		session_start();
		/* Note: if you really are using PHP's default sessions (really?),
		you may experience unexpected session loss.  This is likely
		not a bug in Escher, but a result of vendor-specific behavior
		of your operating system.  Debian is known to override PHP's default
		session garbage collection. */

		// Create the cookie w/ proper values
		$this->updateCookie();
		$this->cookieExists = TRUE;
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
		$result = isset($_SESSION['_FLASH'][$name])
			? $_SESSION['_FLASH'][$name]
			: NULL;
		if (!$keep) {
			if (isset($_SESSION)) {
				unset($_SESSION['_FLASH'][$name]);
			}
		}
		return $result;
	}
	
	protected function pruneFlash() {
		if (isset($_SESSION)) {
			unset($_SESSION['_FLASH']);
		}
	}
		
	function close() {
		// Just run once
		static $closed;
		if ($closed) { return; }
		$closed = TRUE;

		// Prune flash if not explicity preserving it
		if (!$this->preserveFlash) { $this->pruneFlash(); }

		// Finish handling the session/cookie data
		if ($this->cookieExists) {
			if (empty($_SESSION)) {
				setcookie($this->cookieName,'',NOW-24*60*60,$this->cookiePath,$this->cookieDomain);
			}
		} elseif (!empty($_SESSION)) {
			$vars = $_SESSION;
			$this->startSession();
			$_SESSION = $vars;
		}
		session_write_close();

		// Clear all output buffers
		while(ob_get_level()) { ob_end_flush(); }
		flush();
	}

	function openHandler($save_path,$session_name) {}
	function closeHandler() {}
	function readHandler($id) {}
	function writeHandler($id,$data) {}
	function destroyHandler($id) {}
	function garbageHandler($lifetime) {}
}