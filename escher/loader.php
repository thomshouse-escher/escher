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
	 * @param string|array $flavor The type of interface to load, or an array containing the plugin name and interface type, respectively.
	 * @return string|false Returns the name of the interface class, or false on failure.
	 */
	public function HelperClass($helper,$type='default') {
		$type = strtolower($type);
		if (is_array($helper)) {
			array_map('strtolower',$helper);
			if ($type=='default') {
				$filename = "helper.{$helper[1]}.php";
				$classname = "Plugin_{$helper[0]}_Helper_{$helper[1]}";
			} else {
				$filename = "helper.{$helper[1]}.$type.php";
				$classname = "Plugin_{$helper[0]}_Helper_{$helper[1]}_$type";
			}
			if (Load::inc(ESCHER_DOCUMENT_ROOT."/plugins/{$helper[0]}/helpers/{$helper[1]}/$filename")
				&& class_exists($classname)) {
					return $classname;
			} else {
				return false;
			}
		} else {
			$helper = strtolower($helper);
			Load::inc(ESCHER_REAL_PATH."/helpers/$helper/helper.$helper.php");
			if ($type=='default') {
				return class_exists("Helper_$helper") ? "Helper_$helper" : false;
			}
			if (Load::inc(ESCHER_REAL_PATH."/helpers/$helper/$type/helper.$helper.$type.php")
				&& class_exists("Helper_{$helper}_{$type}")) {
					return "Helper_{$helper}_{$type}";
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
	public function Helper($helper,$type='default',$args=NULL) {
		if ($classname = Load::HelperClass($helper,$type)) {
			$newhelper = new $classname($args);
			if (is_array($helper)) {
				$newhelper->plugin = strtolower($helper[0]);
				$newhelper->type = strtolower($helper[1]);
			} else {
				$newhelper->type = strtolower($helper);
			}
			$newhelper->flavor = strtolower($type);
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
	public function PersistentHelper($name,$helper,$type='default',$args=NULL) {
		static $PHelpers = array();
		// Ensure that global interfaces are being accessed properly
		if ($name=='global' && !self::isInternalCall()) { return false; }
		if (is_array($helper)) {
			array_map('strtolower',$helper);
			$plugin = $helper[0];
			$helpername = $helper[1];
		} else {
			$plugin = 'core';
			$helpername = strtolower($helper);
		}
		$type = strtolower($type);
		if (!empty($PHelpers[$helpername][$plugin][$type][$name])) {
			return $PHelpers[$helpername][$plugin][$type][$name];
		}
		if ($classname = Load::HelperClass($helper,$type)) {
			$PHelpers[$helpername][$plugin][$type][$name] = new $classname($args);
			if ($plugin!='core') {
				$PHelpers[$helpername][$plugin][$type][$name]->plugin = $plugin;
			}
			$PHelpers[$helpername][$plugin][$type][$name]->type = $helpername;
			$PHelpers[$helpername][$plugin][$type][$name]->flavor = $type;
			return $PHelpers[$helpername][$plugin][$type][$name];
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
		return Load::PersistentHelper('global','acl');
	}

	/**
	 * Shorthand for loading the default cache handler for the current Escher configuration.
	 * @return object|bool Returns the cache helper object, or false on failure.
	 */
	public function Cache($name='default') {
		$CFG = Load::Config();
		if (empty($CFG['cache'])) { return false; }
		$cache = $CFG['datasource'][$CFG['cache']];
		if (is_array($cache['type'])) {
			return Load::PersistentHelper($name,array($cache['type'][0],'cache'),$cache['type'][1],$cache['settings']);
		} else {
			return Load::PersistentHelper($name,'cache',$cache['type'],$cache['settings']);
		}
	}
	
	/**
	 * Shorthand for loading the config helper.
	 * @return object Returns the config helper.
	 */
	public function Config() {
		self::$internalCall = true;
		return Load::PersistentHelper('global','config');
	}
	public function CFG() { return self::Config(); } // Shorthand

	/**
	 * Shorthand for loading the default database for the current Escher configuration.
	 * @return object|bool Returns the database helper object, or false on failure.
	 */
	public function DB($name='default') {
		$CFG = Load::Config();
		$args = $CFG['database'][$name];
		$type = $args['type'];
		unset($args['type']);
		if (is_array($type)) {
			return Load::PersistentHelper($name,array($type[0],'database'),$type[1],$args);
		} else {
			return Load::PersistentHelper($name,'database',$type,$args);
		}
	}
	
	/**
	 * Shorthand for loading a Datasource object.
	 * @return object Returns the Datasource object.
	 */
	public function Datasource($definition='db') {
		$CFG = Load::Config();
		if ($definition=='db' && !isset($CFG['datasource']['db'])) {
			return Load::PersistentHelper('db','datasource','db');
		} elseif ($definition=='arrcache' && !isset($CFG['datasource']['arrcache'])) {
			return Load::PersistentHelper('arrcache','datasource','arrcache');
		} elseif (isset($CFG['datasource'][$definition]['type'])) {
			$type = $CFG['datasource'][$definition]['type'];
			$settings = !empty($CFG['datasource'][$definition]['settings'])
				? $CFG['datasource'][$definition]['settings']
				: array();
			if (is_array($type)) {
				return Load::PersistentHelper($definition,array($type[0],'datasource'),$type,$settings);
			} else {
				return Load::PersistentHelper($definition,'datasource',$type,$settings);
			}
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
	public function Filter($type='html',$args=NULL) {
		if (is_array($type)) {
			return Load::Helper(array($type[0],'output'),$type[1],$args);
		} else {
			return Load::Helper('filter',$type,$args);
		}
	}
	
	/**
	 * Shorthand for loading a Headers object.
	 * @return object Returns the Headers object.
	 */
	public function Headers() {	
		self::$internalCall = true;
		return Load::PersistentHelper('global','headers');
	}
	
	/**
	 * Shorthand for loading a Hooks object.
	 * @return object Returns the Hooks object.
	 */
	public function Hooks() {	
		self::$internalCall = true;
		return Load::PersistentHelper('global','hooks');
	}
	
	/**
	 * Shorthand for loading an Input object.
	 * @return object Returns the Input object.
	 */
	public function Input($type='default',$args=NULL) {
		if (is_array($type)) {
			return Load::Helper(array($type[0],'input'),$type[1],$args);
		} else {
			return Load::Helper('input',$type,$args);
		}
	}
	
	/**
	 * Shorthand for loading a Lockout object.
	 * @return object Returns the Lockout object.
	 */
	public function Lockout($type='default',$args=NULL) {
		if (is_array($type)) {
			return Load::Helper(array($type[0],'lockout'),$type[1],$args);
		} else {
			return Load::Helper('lockout',$type,$args);
		}
	}
	
	/**
	 * Shorthand for loading an Output object.
	 * @return object Returns the Output object.
	 */
	public function Output($type='php',$args=NULL) {
		if (is_array($type)) {
			return Load::Helper(array($type[0],'output'),$type[1],$args);
		} else {
			return Load::Helper('output',$type,$args);
		}
	}
	
	/**
	 * Shorthand for loading the default router for the current Escher configuration.
	 * @return object|bool Returns the router helper object, or false on failure.
	 */
	public function Router($path=NULL) {
		$CFG = Load::Config();
		$args = $CFG['router'];
		$type = $args['type'];
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
			if (is_array($type)) {
				return Load::PersistentHelper('global',array($type[0],'router'),$type[1],$args);
			} else {
				return Load::PersistentHelper('global','router',$type,$args);
			}
		} else {
			if (is_array($type)) {
				return Load::Helper(array($type[0],'router'),$type[1],$args);
			} else {
				return Load::Helper('router',$type,$args);
			}
		}
	}

	/**
	 * Shorthand for loading the default session for the current Escher configuration.
	 * @return object|bool Returns the session helper object, or false on failure.
	 */
	public function Session() {
		$CFG = Load::Config();
		$args = $CFG['session'];
		$type = $args['type'];
		unset($args['type']);
		self::$internalCall = true;
		if (is_array($type)) {
			return Load::PersistentHelper('global',array($type[0],'session'),$type[1],$args);
		} else {
			return Load::PersistentHelper('global','session',$type,$args);
		}
	}
	
	/**
	 * Shorthand for loading the UI helper.
	 * @return object|bool Returns the UI helper object, or false on failure.
	 */
	public function UI() {
		$CFG = Load::Config();
		$args = @$CFG['ui'];
		self::$internalCall = true;
		return Load::PersistentHelper('global','ui','default',$args);
	}
	
	public function User($keys=NULL) {
		if(is_null($keys)) {
			if (empty($_SESSION['user_id'])) {
				return false;
			}
			$keys = $_SESSION['user_id'];
		}
		$user = Load::Model('user',$keys);
		if (!isset($user->user_id)) {
			return false;
		}
		return $user;
	}
	
	/**
	 * Shorthand for loading a UserAgent object.
	 * @return object Returns the UserAgent object.
	 */
	public function UserAgent($type='default',$args=NULL) {
		if (is_array($type)) {
			return Load::Helper(array($type[0],'useragent'),$type[1],$args);
		} else {
			return Load::Helper('useragent',$type,$args);
		}
	}

	/**
	 * Shorthand for loading a UserAuth helper
	 * @return object Returns the UserAuth object.
	 */
	public function UserAuth($name='default') {
		$CFG = Load::Config();
		if (array_key_exists($name,$CFG['userauth'])) {
			$auth = $CFG['userauth'][$name];
			$helper = !empty($auth['plugin'])
				? array($auth['plugin'],'userauth')
				: 'userauth';
			return Load::Helper($helper,$auth['type'],$auth);
		}
		$hooks = Load::Hooks();
		$hookauth = $hooks->getUserAuths();
		if (array_key_exists($name,$hookauth)) {
			$auth = $hookauth[$name];
			return Load::Helper(array($auth[0],'userauth'),$auth[1],$auth[2]);
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
