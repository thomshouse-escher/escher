<?php
/**
 * Plugin base functionality
 *
 * @author Thom Stricklin <thom@thomshouse.net>
 * @package Escher
 * @subpackage Core
*/

/**
 * Plugin base class
 *
 * Provides the base functionality for all Escher plugin definitions.
 *
 * @author Thom Stricklin <thom@thomshouse.net>
 * @package Escher
 * @subpackage Core
 */
class EscherPlugin extends EscherObject {
	/**
	 * Associative array where the key is the event name and
	 * the value is an array of method names.
	 * @var array
	 */
	protected $events = array();
	/**
	 * Associative array where the key is the filter name and
	 * the value is an array of method names.
	 * @var array
	 */
	protected $filters = array();
	/**
	 * Array of models to register to this plugin
	 * @var array
	 */
	protected $models = array();
	/**
	 * Associative array where the key is the output function name
	 * and the value is the real PHP function name.
	 * @var array
	 */
	protected $outputFunctions = array();
	/**
	 * Multidimensional array where the key is the model name and
	 * the value is an array of schema notation.
	 * @var array
	 */
	protected $schemaFields = array();
	/**
	 * Associative array where the key is the auth name and
	 * the value is the plugin's associated userauth helper.
	 * @var array
	 */
	protected $userAuth = array();
	/**
	 * Associative array where the key is the path of the static route and
	 * the value is the array of route info. This array follows the same
	 * format as $static_routes in config.php.
	 * @var array
	 */
	protected $staticRoutes = array();

	/**
	 * Reads the object properties and registers the hooks.
	 */
	function loadHooks() {
		$hooks = Load::Hooks();
		
		// Events
		foreach($this->events as $e => $methods) {
			$methods = (array)$methods;
			foreach($methods as $m) {
				$priority = 0;
				if (is_array($m)) { list($priority,$m) = $m; }
				$hooks->registerEvent($e,array($this,$m),$priority);
			}
		}

		// Filters
		foreach($this->filters as $f => $methods) {
			foreach($methods as $m) {
				$priority = 0;
				if (is_array($m)) { list($priority,$m) = $m; }
				$hooks->registerFilter($f,array($this,$m),$priority);
			}
		}

		// Models and schema fields
		$hooks->registerModelPlugin($this->models,$this->_p());
		foreach($this->schemaFields as $model => $fields) {
			$hooks->registerSchemaFields($model,$fields);
		}

		// Output functions
		if (!empty($this->outputFunctions)) {
			$function_file = ESCHER_DOCUMENT_ROOT.'/plugins/'.
				$this->_p().'/functions.php';
			if (!file_exists($function_file)) { $function_file = NULL; }
			foreach($this->outputFunctions as $funcname => $realname) {
				$realname = "__escher_{$this->_p()}_$realname";
				if (!function_exists($realname) && !is_null($function_file)) {
					include_once($function_file);
					if (!function_exists($realname)) { continue; }
				}
				$hooks->registerOutputFunction($funcname,$realname);
			}
		}

		// Userauth helpers
		foreach($this->userAuth as $name => $helper) {
			$args = NULL;
			if (is_array($helper)) { list($helper,$args) = $helper; }
			$hooks->registerUserAuth($name,$this->_p(),$helper,$args);
		}

		// Static Routes
		foreach($this->staticRoutes as $path => $options) {
			$hooks->registerStaticRoute($path,$options);
		}
	}

	/**
	 * Returns the name of the current plugin
	 * @return string Plugin name
	 */
	final function _p() {
		$class = get_class($this);
		return substr($class,7);
	}
}
