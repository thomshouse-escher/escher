<?php

/**
 * Initialization code for Escher.
 *
 * @author Thom Stricklin <thom@thomshouse.net>
 * @author Andrew Detwiler <adetwiler@adidamnetworks.com>
 * @package Escher
 * @subpackage Init
 */

/**
 * File path of this Escher installation
 */
define('ESCHER_FILE_PATH',dirname(__FILE__));

/**
 * Class definition for initialization object
 *
 * An EscherInit object provides two options for initialization. The first
 * is main(), for handling HTTP requests. The second is cron(), for running
 * scheduled or automated functionality.
 *
 * @author Thom Stricklin <thom@thomshouse.net>
 * @author Andrew Detwiler <adetwiler@adidamnetworks.com>
 * @package Escher
 * @subpackage Init
 */

class EscherInit {
	/**
	 * Raw config arguments (pre-helper)
	 * @var array
	 */
	protected $CFG;
	/**
	 * File-system root of Escher installation
	 * @var string
	 */
	protected $fileroot;

	/**
	 * Class constructor
	 * @param string File-system root of Escher installation
	 */
	function __construct() {
		$this->fileroot = ESCHER_DOCUMENT_ROOT;
	}

	/**
	 * Handle HTTP request
	 *
	 * The main() function is the initialization function for handling an
	 * HTTP request sent to Escher.  This process involves loading core
	 * classes, hooks, and initializing the router object.
	 * 
	 * The actual interpretation of the HTTP request occurs at the router.
	 */
	function main() {
		$this->initCommon();
		$this->initSession();

		$router = Load::Router();
		if (!isset($router->controller)) {
			Load::Error('404');
		}
		$controller = Load::Controller($router->controller,@$router->args);
		$controller->router = $router;
		if (!$controller->execute() && empty($controller->data)) {
			Load::Error('404');
		}

		// Auto-display results
		$controller->display($controller->getCalledAction(),$controller->data);
	}

	/**
	 * Handle cron execution
	 *
	 * The cron() function is the initialization function for handling the
	 * execution of scheduled or automated functionality. Cron handling depends
	 * on command-line arguments passed to cron.php in the install root.
	 *
	 * Command-line arguments should be, in order:
	 *
	 * * Plugin name (or '-' if none) of the controller to run.
	 * * Controller name.
	 * * Function name.
	 * * Arguments to pass (optional, separated by spaces).
	 */
	function cron() {
		$this->initCommon();

		$CFG = $this->CFG;

		if (empty($_SERVER['argv']) || sizeof($_SERVER['argv']) < 4) { return; }

		$argv = $_SERVER['argv'];

		array_shift($argv);
		$plugin = array_shift($argv);
		$controller = array_shift($argv);
		if ($plugin != '-') {
			$controller = array($plugin,$controller);
		}
		$function = array_shift($argv);
		$c_obj = Load::Controller($controller);
		print_r($c_obj->$function($argv));
		echo "\n";
	}

	/**
	 * Initialize all common dependencies
	 */
	protected function initCommon() {
		$this->initEnvironment();
		$this->initLoader();
		$this->initClasses();
		$this->initConfig();
		$this->initPatterns();
		$this->initHooks();
	}

	/**
	 * Initialize environment settings
	 */
	protected function initEnvironment() {
		require_once($this->fileroot.'/escher/environment.php');
	}

	/**
	 * Initialize raw config values
	 *
	 * @global array Global $CFG provided for backwards-compatibility
	 */
	protected function initConfig() {
		$this->CFG = Load::Config(TRUE);
		$this->CFG->loadSettings();

		/**
		 * @global array $CFG
		 * @deprecated Use Load::CFG() instead.
		 */
		$CFG = Load::Config();
		$CFG = $this->CFG;
	}

	/**
	 * Initialize loader class
	 */
	protected function initLoader() {
		require_once($this->fileroot.'/escher/loader.php');
	}

	/**
	 * Initialize core classes
	 */
	protected function initClasses() {
		Load::core('EscherObject.php','controller/Controller.php',
			'model/Model.php','Helper.php');

		// Using class aliases provides simpler extensibility
		class_alias('EscherController','Controller');
		class_alias('EscherHelper','Helper');
		class_alias('EscherModel','Model');
	}

	/**
	 * Initialize common pattern classes
	 */
	protected function initPatterns() {
		Load::core('patterns/Model_File.php');
	}

	/**
	 * Initialize plugin hooks
	 */
	protected function initHooks() {
		$hooks = Load::Hooks();
		$hooks->loadPluginHooks($this->CFG['active_plugins']);
	}

	/**
	 * Initialize session
	 */
	protected function initSession() {
		if (isset($this->CFG['session']['type']) && $this->CFG['session']['type']!='none') {
			Load::Session();
			$hooks = Load::Hooks();
			$hooks->runEvent('session_start');
		}
	}
}
