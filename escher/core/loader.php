<?php

/**
 * loader.php
 *
 * Contains the "Load" class, which acts as the service locator and file-loading
 * utility class for Escher.
 * @author Thom Stricklin <thom@thomshouse.net>
 * @version 1.0
 * @package Escher
 */


/**
 * Service locator and file-loading utility class.
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
     * @param string|array $name Controller name or plugin-name array.
     * @param array $args An array of arguments to pass along to the controller.
     * @return Controller|bool Returns an instance of the controller.
     */
    public static function Controller($name,$args=NULL) {
        if (is_array($name)) {
            array_map('strtolower',$name);
            if (Load::inc(ESCHER_DOCUMENT_ROOT.'/plugins/'.$name[0]
                .'/controllers/'.$name[1].'/controller.'.$name[1].'.php')
                && class_exists("Plugin_{$name[0]}_Controller_{$name[1]}")
            ) {
                $classname = "Plugin_{$name[0]}_Controller_{$name[1]}";
            } else {
                return false;
            }
        } else {
            $name = strtolower($name);
            if (Load::inc(ESCHER_REAL_PATH.'/controllers/'.$name.'/controller.'
                .$name.'.php') && class_exists("Controller_$name")
            ) {
                $classname = "Controller_$name";
            } else {
                return false;
            }
        }
        return new $classname($args);
    }

    /**
     * Loads a model object.
     * @static
     * @param string|array|object $name Model, name, or plugin-name array.
     * @param int|array $key Primary key or array of name-value pairs.
     * @return Model|bool Returns an instance of the model.
     */
    public static function Model($name,$key=NULL) {
        if (is_a($name,'model')) {
            $classname = get_class($name);
        } elseif (is_array($name)) {
            if (is_null($name[0])) {
                $name = strtolower($name[1]);
                if (Load::inc(ESCHER_REAL_PATH.'/models/'.$name.'/model.'
                    .$name.'.php') && class_exists("Model_$name")
                ) {
                    $classname = "Model_$name";
                } else {
                    return false;
                }
            } else {
                array_map('strtolower',$name);
                if (Load::inc(ESCHER_DOCUMENT_ROOT.'/plugins/'.$name[0].'/models/'
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
                if (Load::inc(ESCHER_DOCUMENT_ROOT.'/plugins/'.$plugin.'/models/'.$name.'/model.'.$name.'.php')
                    && class_exists("Plugin_{$plugin}_Model_{$name}")) {
                    $classname = "Plugin_{$plugin}_Model_{$name}";
                } else {
                    return false;
                }
            } else {
                if (Load::inc(ESCHER_REAL_PATH.'/models/'.$name.'/model.'.$name.'.php') && class_exists("Model_$name")) {
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
            if (sizeof($result->_savedValues)==0) {
                return false;
            }
            return $result;
        }
    }

    /**
     * Loads a the class files for a helper.
     * @static
     * @param string|array $helper The helper name or plugin-name array.
     * @param string $type Special subtype of helper to load.
     * @return string|bool Returns the name of the interface class.
     */
    public static function HelperClass($helper,$type='default') {
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
     * @static
     * @param string $helper The name of the interface to load.
     * @param string|array $type The type of interface to load, or an array containing the plugin name and interface type, respectively.
     * @param array $args An array of arguments to pass to the interface.
     * @return Helper|bool Returns a new instance of the interface class, or false on failure.
     */
    public static function Helper($helper,$type='default',$args=NULL) {
        if ($classname = Load::HelperClass($helper,$type)) {
            $newhelper = new $classname($args);
            if (is_array($helper)) {
                $newhelper->plugin = strtolower($helper[0]);
                $newhelper->helper = strtolower($helper[1]);
            } else {
                $newhelper->helper = strtolower($helper);
            }
            $newhelper->type = strtolower($type);
            return $newhelper;
        } else {
            return false;
        }
    }

    /**
     * Loads a persistent (singleton-style) instance of an interface object.
     * @static
     * @param string $name The name of the interface to load.
     * @param string|array $helper The type of interface to load, or an array containing the plugin name and interface type, respectively.
     * @param string $type The "namespace", i.e. a unique identifier, of this persistent interface.
     * @param array $args An array of arguments to pass to the interface.
     * @return Helper|bool Returns a persistent instance of the interface class, or false on failure.
     */
    public static function PersistentHelper($name,$helper,$type='default',$args=NULL) {
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
            $PHelpers[$helpername][$plugin][$type][$name]->helper = $helpername;
            $PHelpers[$helpername][$plugin][$type][$name]->type = $type;
            return $PHelpers[$helpername][$plugin][$type][$name];
        } else {
            return false;
        }
    }

    /**
     * Loads "core" files--starter classes and logic too low-level for MVC or Plugin-based structure.
     * @static
     * @param string|array Either a filename or an array of filenames to load.
     * @return bool Returns true on success, false on failure.
     */
    public static function core($arg) {
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
     * @static
     * @param string|array Either a filename or an array of filenames to load.
     * @return bool Returns true on success, false on failure.
     */
    public static function lib($arg) {
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
                return Load::inc(ESCHER_DOCUMENT_ROOT.'/plugins/'.$arg[0].'/lib/'.$arg[1]);
            } else {
                return Load::inc(ESCHER_REAL_PATH.'/lib/'.$arg);
            }
        }
    }

    /**
     * Shorthand for loading an ACL object.
     * @return Helper_acl Returns the ACL object.
     */
    public static function ACL() {
        self::$internalCall = true;
        return Load::PersistentHelper('global','acl');
    }

    /**
     * Shorthand for loading the default cache handler for the current Escher configuration.
     * @static
     * @param string|null Name of the cache datasource, or else the default.
     * @return object|bool Returns the cache helper object, or false on failure.
     */
    public static function Cache($name=NULL) {
        $CFG = Load::Config();
        if (is_null($name)) {
            foreach($CFG['datasource_cache_order']['all'] as $n) {
                if ($CFG['datasource'][$n]['helper']=='cache') {
                    $name = $n;
                    break;
                }
            }
            if (is_null($name)) { return FALSE; }
        }
        if (array_key_exists($name,$CFG['datasource'])
            && !empty($CFG['datasource'][$name]['helper'])
            && $CFG['datasource'][$name]['helper']=='cache'
        ) {
            $args = $CFG['datasource'][$name];
        } else {
            return FALSE;
        }
        $type = $args['type'];
        unset($args['helper'],$args['type']);
        if (is_array($type)) {
            return Load::PersistentHelper($name,array($type[0],'cache'),
                $type[1],$args);
        } else {
            return Load::PersistentHelper($name,'cache',$type,$args);
        }
    }

    /**
     * Shorthand for loading the config helper.
     * @static
     * @return Helper_config Returns the config helper.
     */
    public static function Config() {
        self::$internalCall = true;
        return Load::PersistentHelper('global','config');
    }

    /**
     * Alias for config helper
     * @return Helper_config
     */
    public static function CFG() { return self::Config(); } // Shorthand

    /**
     * Shorthand for loading the default database for the current Escher configuration.
     * @static
     * @param string|null $name Name of database, or first available.
     * @return Helper_database Returns the database helper object, or false on failure.
     */
    public static function DB($name=NULL) {
        $CFG = Load::Config();
        if (is_null($name)) {
            foreach($CFG['datasource_order']['all'] as $n) {
                if ($CFG['datasource'][$n]['helper']=='database') {
                    $name = $n;
                    break;
                }
            }
            if (is_null($name)) { return FALSE; }
        }
        if (array_key_exists($name,$CFG['datasource'])
            && !empty($CFG['datasource'][$name]['helper'])
            && $CFG['datasource'][$name]['helper']=='database'
        ) {
            $args = $CFG['datasource'][$name];
        } else {
            return FALSE;
        }
        $type = $args['type'];
        unset($args['helper'],$args['type']);
        if (is_array($type)) {
            return Load::PersistentHelper($name,array($type[0],'database'),
                $type[1],$args);
        } else {
            return Load::PersistentHelper($name,'database',$type,$args);
        }
    }

    /**
     * Shorthand for loading a config-defined Datasource.
     * @static
     * @param string $definition Named definition for the datasource.
     * @return Helper_datasource|bool Returns the Datasource object.
     */
    public static function Datasource($definition) {
        $CFG = Load::Config();
        if (isset($CFG['datasource'][$definition]['helper'])) {
            $settings = $CFG['datasource'][$definition];
            $helper = $settings['helper'];
            unset($settings['helper']);
            if (is_array($helper)) {
                return Load::PersistentHelper($definition,
                    array($helper[0],'datasource'),$helper[1],$settings);
            } else {
                return Load::PersistentHelper($definition,'datasource',
                    $helper,$settings);
            }
        }
        return false;
    }

    /**
     * Shorthand for displaying error pages.
     * @static
     * @param string $error Status code number or other named error.
     * @param array $args Arguments to pass to error controller.
     */
    public static function Error($error='404',$args=array()) {
        $CFG=Load::CFG();
        array_unshift($args,$error);
        // If error controller is set in the config.
        if (isset($CFG['errorController'])) {
            $controller = Load::Controller($CFG['errorController'],$args);
            // If this error controller is not set in the config-- fall back on default.
        } else {
            $controller = Load::Controller('errors',$args);
        }
        die($controller->Execute());
    }

    /**
     * Shorthand for loading a Filter object.
     * @static
     * @param string|array $type Type of filter helper.
     * @param array|null $args Arguments to pass to filter helper.
     * @return Helper_filter Returns the Filter object.
     */
    public static function Filter($type='html',$args=NULL) {
        if (is_array($type)) {
            return Load::Helper(array($type[0],'output'),$type[1],$args);
        } else {
            return Load::Helper('filter',$type,$args);
        }
    }

    /**
     * Shorthand for loading a Headers object.
     * @static
     * @return Helper_headers Returns the Headers object.
     */
    public static function Headers() {
        self::$internalCall = true;
        return Load::PersistentHelper('global','headers');
    }

    /**
     * Shorthand for loading a Hooks object.
     * @static
     * @return Helper_hooks Returns the Hooks object.
     */
    public static function Hooks() {
        self::$internalCall = true;
        return Load::PersistentHelper('global','hooks');
    }

    /**
     * Shorthand for loading an Input object.
     * @static
     * @param string|array $type Type of input helper.
     * @param array|null $args Array of arguments.
     * @return Helper_input Returns the Input object.
     */
    public static function Input($type='default',$args=NULL) {
        if (is_array($type)) {
            return Load::Helper(array($type[0],'input'),$type[1],$args);
        } else {
            return Load::Helper('input',$type,$args);
        }
    }

    /**
     * Shorthand for loading a Lockout object.
     * @static
     * @param string|array $type Type of lockout helper.
     * @param array|null $args Array of arguments.
     * @return Helper_lockout Returns the Lockout object.
     */
    public static function Lockout($type='default',$args=NULL) {
        if (is_array($type)) {
            return Load::Helper(array($type[0],'lockout'),$type[1],$args);
        } else {
            return Load::Helper('lockout',$type,$args);
        }
    }

    /**
     * Shorthand for loading an Output object.
     * @static
     * @param string|array $type Type of output helper.
     * @param array|null $args Optional arguments to pass to output helper.
     * @return Helper_output Returns the Output object.
     */
    public static function Output($type='php',$args=NULL) {
        if (is_array($type)) {
            return Load::Helper(array($type[0],'output'),$type[1],$args);
        } else {
            return Load::Helper('output',$type,$args);
        }
    }

    /**
     * Shorthand for loading the default router.
     * @static
     * @param string $path Path to associate with this router instance.
     * @return Helper_router Returns the router helper object.
     */
    public static function Router($path=NULL) {
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
     * Shorthand for loading the session helper.
     * @static
     * @return Helper_session Returns the session helper object.
     */
    public static function Session() {
        static $session;
        if (is_null($session)) {
            $CFG = Load::Config();
            $session = $CFG['session'];
        }
        $args = $session;
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
     * @static
     * @return Helper_ui Returns the UI helper object.
     */
    public static function UI() {
        $CFG = Load::Config();
        $args = @$CFG['ui'];
        self::$internalCall = true;
        return Load::PersistentHelper('global','ui','default',$args);
    }

    /**
     * @static
     * @param null $keys
     * @return bool|Model_user
     */
    public static function User($keys=NULL) {
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
     * Shorthand for loading a useragent object.
     * @param string|array $type Type of useragent helper or plugin-type array.
     * @param array|null $args Optional arguments to pass to helper.
     * @return object Returns the UserAgent object.
     */
    public static function UserAgent($type='default',$args=NULL) {
        if (is_array($type)) {
            return Load::Helper(array($type[0],'useragent'),$type[1],$args);
        } else {
            return Load::Helper('useragent',$type,$args);
        }
    }

    /**
     * Shorthand for loading a UserAuth helper
     * @param string $name Userauth helper name (as named in config or hooks).
     * @return object Returns the UserAuth object.
     */
    public static function UserAuth($name='default') {
        $CFG = Load::Config();
        if (array_key_exists($name,$CFG['userauth'])) {
            $auth = $CFG['userauth'][$name];
            $helper = !empty($auth['plugin'])
                ? array($auth['plugin'],'userauth')
                : 'userauth';
            return Load::Helper($helper,$auth['type'],$auth);
        }
        $hooks = Load::Hooks();
        $authHooks = $hooks->getUserAuths();
        if (array_key_exists($name,$authHooks)) {
            $auth = $authHooks[$name];
            return Load::Helper(array($auth[0],'userauth'),$auth[1],$auth[2]);
        }
        $auth = $CFG['userauth']['default'];
        return Load::Helper('userauth',$auth['type'],$auth);
    }

    /**
     * Determines if one method of the Load class is calling another.
     * Used internally by Load to prevent manipulation of system components.
     * @uses Load::$internalCall Checks for a true value and resets to false.
     * @return bool Returns the checked value of $internalCall.
     */
    private static function isInternalCall() {
        $result = self::$internalCall;
        self::$internalCall = false;
        return (bool)$result;
    }

    /**
     * Wrapper for include_once.
     * @param string $filename Name of the file to include.
     * @return mixed Returns the results of include_once().
     */
    public static function inc($filename) {
        return include_once $filename;
    }


    /**
     * Wrapper for require_once.
     * @param string $filename Name of the file to require.
     * @return mixed Returns the results of require_once().
     */
    public static function req($filename) {
        return require_once $filename;
    }
}
