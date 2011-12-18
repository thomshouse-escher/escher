<?php

/**
 * loader.php
 * 
 * Contains the "Load" class, which is the factory for all objects in Escher.
 * @author Thom Stricklin <code@thomshouse.net>
 * @version 1.0
 * @package Escher
 */


/**
 * Factory class for creating all objects and loading all required files in Escher.
 * @package Escher
 */
class Load {
	/**
	 * Used by Load::isInternalCall to prevent hacking of core functionality
	 * @var bool
	 */
	private static $internalCall = false;

	/**
	 * Loads a controller object.  
	 * @param string|array $name The name of the controller to load, or an array containing the plugin name and controller name, respectively.
	 * @param array $args An array of arguments to pass along to the controller.
	 * @return object|false Returns an instance of the controller, or false on failure.
	 */
	public function Controller($name,$args=NULL) {
		if (is_array($name)) {
			array_map('strtolower',$name);
			if (Load::req(ESCHER_DOCUMENT_ROOT.'/plugins/'.$name[0].'/controllers/'.$name[1].'/controller.'.$name[1].'.php')
				&& class_exists("Plugin_{$name[0]}_Controller_{$name[1]}")) {
					$classname = "Plugin_{$name[0]}_Controller_{$name[1]}";
			} else {
				return false;
			}
		} else {
			$name = strtolower($name);
			if (Load::req(ESCHER_REAL_PATH.'/controllers/'.$name.'/controller.'.$name.'.php') && class_exists("Controller_$name")) {
				$classname = "Controller_$name";
			} else {
				return false;
			}	
		}
		return new $classname($args);
	}
	
	/**
	 * Loads a model object.  
	 * @param string|array $name The name of the model to load, or an array containing the plugin name and model name, respectively.
	 * @param int|array $key The key of the model object to load.  Either the primary key (as an int) or an array of name-value pairs.
	 * @return object|false Returns an instance of the model, or false on failure.
	 */
	public function Model($name,$key=NULL) {
		if (is_array($name)) {
			if (is_null($name[0])) {
				$name = strtolower($name[1]);
				if (Load::req(ESCHER_REAL_PATH.'/models/'.$name.'/model.'.$name.'.php') && class_exists("Model_$name")) {
					$classname = "Model_$name";
				} else {
					return false;
				}	
			} else {
				array_map('strtolower',$name);
				if (Load::req(ESCHER_DOCUMENT_ROOT.'/plugins/'.$name[0].'/models/'
						.$name[1].'/model.'.$name[1].'.php')
					&& class_exists("Plugin_{$name[0]}_Model_{$name[1]}")) {
						$classname = "Plugin_{$name[0]}_Model_{$name[1]}";
				} else {
					return false;
				}
			}
		} else {
			$name = strtolower($name);
			$hooks = Load::Hooks();
			if ($plugin = $hooks->getModelPlugin($name)) {
				if (Load::req(ESCHER_DOCUMENT_ROOT.'/plugins/'.$plugin.'/models/'.$name.'/model.'.$name.'.php')
					&& class_exists("Plugin_{$plugin}_Model_{$name}")) {
						$classname = "Plugin_{$plugin}_Model_{$name}";
				} else {
					return false;
				}
			} else {
				if (Load::req(ESCHER_REAL_PATH.'/models/'.$name.'/model.'.$name.'.php') && class_exists("Model_$name")) {
					$classname = "Model_$name";
				} else {
					return false;
				}	
			}
		}
		if (is_null($key)) {
			return new $classname();
		} else {
			$result = new $classname($key);
			$diff = array_diff_assoc(get_object_vars($result),get_class_vars(get_class($result)));
			if (empty($diff)) {
				return false;
			}
			return $result;
		}
	}
	
	/**
	 * Loads a the class files for an interface in Escher.
	 * @param string|array $name The name of the interface to load.
	 * @param string|array $type The type of interface to load, or an array containing the plugin name and interface type, respectively.
	 * @return string|false Returns the name of the interface class, or false on failure.
	 */
	public function HelperClass($name,$type=NULL) {
		$name = strtolower($name);
		if (is_array($type)) {
			array_map('strtolower',$type);
			if (Load::inc(ESCHER_DOCUMENT_ROOT.'/plugins/'.$type[0].'/helpers/'.$name.'/helper.'.$name.'_'.$type[1].'.php')
				&& class_exists("Plugin_{$type[0]}_Helper_{$name}_{$type[1]}")) {
					return "Plugin_{$type[0]}_Helper_{$name}_{$type[1]}";
			} else {
				return false;
			}
		} elseif (is_null($type)) {
			return Load::inc(ESCHER_REAL_PATH.'/helpers/'.$name.'/helper.'.$name.'.php');
		} else {
			$type = strtolower($type);
			if (Load::inc(ESCHER_REAL_PATH.'/helpers/'.$name.'/helper.'.$name.'.php')
				&& Load::inc(ESCHER_REAL_PATH.'/helpers/'.$name.'/'.$type.'/helper.'.$name.'.'.$type.'.php')
				&& class_exists("Helper_{$name}_{$type}")) {
					return "Helper_{$name}_{$type}";
			} else {
				return false;
			}
		}
	}	
	
	/**
	 * Loads a new instance of an interface object.
	 * @param string $name The name of the interface to load.
	 * @param string|array $type The type of interface to load, or an array containing the plugin name and interface type, respectively.
	 * @param array $args An array of arguments to pass to the interface.
	 * @return object|false Returns a new instance of the interface class, or false on failure.
	 */
	public function Helper($name,$type,$args=NULL) {
		if ($classname = Load::HelperClass($name,$type)) {
			$newhelper = new $classname($args);
			if (is_array($type)) {
				$newhelper->plugin = strtolower($type[0]);
				$newhelper->type = strtolower($type[1]);
			} else {
				$newhelper->type = strtolower($type);
			}
			return $newhelper;
		} else {
			return false;
		}	
	}
	
	/**
	 * Loads a persistent (singleton-style) instance of an interface object.
	 * @param string $name The name of the interface to load.
	 * @param string|array $type The type of interface to load, or an array containing the plugin name and interface type, respectively.
	 * @param string $namespace The "namespace", i.e. a unique identifier, of this persistent interface.
	 * @param array $args An array of arguments to pass to the interface.
	 * @return object|false Returns a persistent instance of the interface class, or false on failure.
	 */
	public function PersistentHelper($name,$type,$namespace,$args=NULL) {
		static $PHelpers = array();
		// Ensure that global interfaces are being accessed properly
		if ($namespace=='global' && !self::isInternalCall()) { return false; }
		if (is_array($type)) {
			array_map('strtolower',$type);
			$plugin = $type[0];
			$typename = $type[1];
		} else {
			$plugin = 'core';
			$typename = strtolower($type);
		}
		$name = strtolower($name);
		if (!empty($PHelpers[$name][$plugin][$typename][$namespace])) {
			return $PHelpers[$name][$plugin][$typename][$namespace];
		}
		if ($classname = Load::HelperClass($name,$type)) {
			$PHelpers[$name][$plugin][$typename][$namespace] = new $classname($args);
			if ($plugin!='core') {
				$PHelpers[$name][$plugin][$typename][$namespace]->plugin = $plugin;
			}
			$PHelpers[$name][$plugin][$typename][$namespace]->type = $typename;
			return $PHelpers[$name][$plugin][$typename][$namespace];
		} else {
			return false;
		}	
	}
	
	/**
	 * Loads "core" files--starter classes and logic too low-level for MVC or Plugin-based structure.
	 * @param string|array $mixed Either a filename or an array of filenames to load.
	 * @return bool Returns true on success, false on failure.
	 */
	public function core($arg) {
		// If loading multiple files...
		$args = func_get_args();
		if (sizeof($args)>1) {
				// Start with a true result
				$result = true;
			foreach($args as $arg) {
				// If any load attempt returns false, current result becomes false
				$result = $result && Load::core($arg);
			}
			return $result;
		} else {
			// If loading a single file, attempt and return the result
			return Load::req(ESCHER_REAL_PATH.'/core/'.$arg);
		}
	}

	/**
	 * Loads library files contained within the core Escher '/lib/' directory.
	 * @param string|array $mixed Either a filename or an array of filenames to load.
	 * @return bool Returns true on success, false on failure.
	 */
	public function lib($arg) {
		// If loading multiple files...
		$args = func_get_args();
		if (sizeof($args)>1) {
				// Start with a true result
				$result = true;
			foreach($args as $arg) {
				// If any load attempt returns false, current result becomes false
				$result = $result && Load::lib($arg);
			}
			return $result;
		} else {
			// If loading a single file, attempt and return the result
			if (is_array($arg)) {
				return Load::req(ESCHER_DOCUMENT_ROOT.'/plugins/'.$arg[0].'/lib/'.$arg[1]);
			} else {
				return Load::req(ESCHER_REAL_PATH.'/lib/'.$arg);
			}
		}
	}
	
	/**
	 * Shorthand for loading an ACL object.
	 * @return object Returns the ACL object.
	 */
	public function ACL() {	
		self::$internalCall = true;
		return Load::PersistentHelper('acl','default','global');
	}

	/**
	 * Shorthand for loading the default cache handler for the current Escher configuration.
	 * @return object|bool Returns the cache helper object, or false on failure.
	 */
	public function Cache($name='default') {
		$CFG = Load::Config();
		if (empty($CFG['cache'])) { return false; }
		$cache = $CFG['datasource'][$CFG['cache']];
		return Load::PersistentHelper('cache',$cache['type'],$name,$cache['settings']);
	}
	
	/**
	 * Shorthand for loading the config helper.
	 * @return object Returns the config helper.
	 */
	public function Config() {
		self::$internalCall = true;
		return Load::PersistentHelper('config','default','global');
	}
	public function CFG() { return self::Config(); } // Shorthand

	/**
	 * Shorthand for loading the default database for the current Escher configuration.
	 * @return object|bool Returns the database helper object, or false on failure.
	 */
	public function DB($name='default') {
		$CFG = Load::Config();
		$args = $CFG['database'][$name];
		unset($args['type']);
		return Load::PersistentHelper('database',$CFG['database'][$name]['type'],$name,$args);
	}
	
	/**
	 * Shorthand for loading a Datasource object.
	 * @return object Returns the Datasource object.
	 */
	public function Datasource($definition='db') {
		$CFG = Load::Config();
		if ($definition=='db' && !isset($CFG['datasource']['db'])) {
			return Load::PersistentHelper('datasource','db','db');
		} elseif ($definition=='arrcache' && !isset($CFG['datasource']['arrcache'])) {
			return Load::PersistentHelper('datasource','arrcache','arrcache');
		} elseif (isset($CFG['datasource'][$definition]['type'])) {
			return Load::PersistentHelper('datasource',$CFG['datasource'][$definition]['type'],$definition,@$CFG['datasource'][$definition]['settings']);
		}
		return false;
	}

	/**
	 * Shorthand for displaying error pages.
	 * @return string Returns the error page as a string.
	 */
	public function Error($error='404',$args=array()) {
		array_unshift($args,$error);
		$session = Load::Session();
		$session->remember_current_request = FALSE;
		$controller = Load::Controller('errors',$args);
		die($controller->Execute());
	}
	
	/**
	 * Shorthand for loading a Filter object.
	 * @return object Returns the Filter object.
	 */
	public function Filter($type='default',$args=NULL) {
		return Load::Helper('filter',$type,$args);
	}
	
	/**
	 * Shorthand for loading a Headers object.
	 * @return object Returns the Headers object.
	 */
	public function Headers() {	
		self::$internalCall = true;
		return Load::PersistentHelper('headers','default','global');
	}
	
	/**
	 * Shorthand for loading a Hooks object.
	 * @return object Returns the Hooks object.
	 */
	public function Hooks() {	
		self::$internalCall = true;
		return Load::PersistentHelper('hooks','default','global');
	}
	
	/**
	 * Shorthand for loading an Input object.
	 * @return object Returns the Input object.
	 */
	public function Input($type='default',$args=NULL) {
		return Load::Helper('input',$type,$args);
	}
	
	/**
	 * Shorthand for loading a Lockout object.
	 * @return object Returns the Lockout object.
	 */
	public function Lockout($type='default',$args=NULL) {
		return Load::Helper('lockout',$type,$args);
	}
	
	/**
	 * Shorthand for loading an Output object.
	 * @return object Returns the Output object.
	 */
	public function Output($type='php',$args=NULL) {
		return Load::Helper('output',$type,$args);
	}
	
	/**
	 * Shorthand for loading the default router for the current Escher configuration.
	 * @return object|bool Returns the router helper object, or false on failure.
	 */
	public function Router($path=NULL) {
		$CFG = Load::Config();
		$args = $CFG['router'];
		unset($args['type']);
		if (!is_null($path)) {
			$args['path'] = $path;
		} elseif (!empty($_SERVER['PATH_INFO'])) {
			$args['path'] = $_SERVER['PATH_INFO'];
		} elseif (!empty($_GET['_PATH_INFO'])) {
			$args['path'] = $_GET['_PATH_INFO'];
			unset($_GET['_PATH_INFO']);
			if (isset($_REQUEST)) { unset($_REQUEST['_PATH_INFO']); }
		} else {
			$args['path'] = '';
		}
		$args['root'] = $CFG['root'];
		if (is_null($path)) {
			self::$internalCall = true;
			return Load::PersistentHelper('router',$CFG['router']['type'],'global',$args);
		} else {
			return Load::Helper('router',$CFG['router']['type'],$args);
		}
	}

	/**
	 * Shorthand for loading the default session for the current Escher configuration.
	 * @return object|bool Returns the session helper object, or false on failure.
	 */
	public function Session() {
		$CFG = Load::Config();
		$args = $CFG['session'];
		unset($args['type']);
		self::$internalCall = true;
		return Load::PersistentHelper('session',$CFG['session']['type'],'global',$args);
	}
	
	/**
	 * Shorthand for loading the UI helper.
	 * @return object|bool Returns the UI helper object, or false on failure.
	 */
	public function UI() {
		$CFG = Load::Config();
		$args = @$CFG['ui'];
		self::$internalCall = true;
		return Load::PersistentHelper('ui','default','global',$args);
	}
	
	public function User($keys=NULL) {
		if(is_null($keys)) {
			if (empty($_SESSION['user_id'])) {
				return false;
			}
			$keys = $_SESSION['user_id'];
		}
		$user = Load::Model('user',$keys);
		if (!isset($user->id)) {
			return false;
		}
		return $user;
	}
	
	/**
	 * Shorthand for loading a UserAgent object.
	 * @return object Returns the UserAgent object.
	 */
	public function UserAgent($type='default',$args=NULL) {
		return Load::Helper('useragent',$type,$args);
	}

	/**
	 * Shorthand for loading a UserAuth helper
	 * @return object Returns the UserAuth object.
	 */
	public function UserAuth($name='default') {
		$CFG = Load::Config();
		if (array_key_exists($name,$CFG['userauth'])) {
			$auth = $CFG['userauth'][$name];
			return Load::Helper('userauth',$auth['type'],$auth);
		}
		$hooks = Load::Hooks();
		$hookauth = $hooks->getUserAuths();
		if (array_key_exists($name,$hookauth)) {
			$auth = $hookauth[$name];
			return Load::Helper('userauth',array($auth[0],$auth[1]),$auth[2]);
		}
		$auth = $CFG['userauth']['default'];
		return Load::Helper('userauth',$auth['type'],$auth);
	}
	
	/**
	 * Determines if one method of the Load class is calling another.
	 * This is useful in methods such as PersistentHelper in which we might want to protect core system objects.
	 * @uses Load::$internalCall Checks $internalCall for a true value, and resets it to false.
	 * @return bool Returns the checked value of $internalCall.
	 */
	private function isInternalCall() {
		$result = self::$internalCall;
		self::$internalCall = false;
		return (bool)$result;
	}
	
	/**
	 * Wrapper for include_once.  Used in case the includes behavior ever needs to be changed in the future.
	 * @param string $filename Name of the file to include.
	 * @return mixed Returns the results of include_once().
	 */
	public function inc($filename) {
		return include_once $filename;
	}

	
	/**
	 * Wrapper for require_once.  Used in case the includes behavior ever needs to be changed in the future.
	 * @param string $filename Name of the file to require.
	 * @return mixed Returns the results of require_once().
	 */
	public function req($filename) {
		return require_once $filename;
	}
}
