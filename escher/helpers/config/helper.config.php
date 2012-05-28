<?php
/**
 * Config helper
 *
 * @author Thom Stricklin <thom@thomshouse.net>
 * @package Escher
 * @subpackage Helpers
*/

/**
 * Config helper
 *
 * The config helper is responsible for loading and merging config settings
 * from multiple sources--defaults, config.php, and database/cache.
 *
 * @author Thom Stricklin <thom@thomshouse.net>
 * @package Escher
 * @subpackage Helpers
 */
class Helper_config extends Helper implements ArrayAccess {
	/**
	 * Install mode flag
	 * @var bool
	 */
	protected $install = FALSE;
	/**
	 * Configuration settings from the database/cache
	 * @var array
	 */
	protected $saved = array();
	/**
	 * All configuration settings
	 * @var array
	 */
	protected $settings = array();
	/**
	 * Configuration settings from defaults and config.php
	 * @var array
	 */
	protected $static = array();

	/**
	 * Config constructor
	 *
	 * Loads the defaults.php file, which handles loading of config.php and
	 * merging/reconciliation of default and specified values.
	 */
	function __construct() {
		if (file_exists(ESCHER_DOCUMENT_ROOT.'/config.php')) {
			require(ESCHER_REAL_PATH.'/core/defaults.php');
			$this->static = array_diff_key(get_defined_vars(),array('this'=>0));
			$this->settings = $this->static;
		} else {
			$this->settings = $this->static = $this->getInstallSettings();
			$this->install = TRUE;
		}
	}

	/**
	 * Save a setting
	 *
	 * Saves an individual config setting to the database.
	 *
	 * @param string Name of the setting
	 * @param mixed Value of the setting
	 */
	function save($name,$value) {
		if (is_string($name)) {
			if (!$model = Load::Model('config',$name)) {
				$model = Load::Model('config');
			}
			$model->assignVars(array(
				'config_id'    => $name,
				'config_value' => $value,
			));
			$model->save();
			if ($cache = Load::Cache()) {
				$cache->delete('escher_config');
			}
			$this->loadSettings();
		}
	}

	/**
	 * Load saved settings
	 *
	 * Loads saved settings from the cache or database
	 */
	function loadSettings() {
		if ($this->install) { return; }
		$cache = Load::Cache();
		$saved = is_object($cache) ? $cache->get('escher_config') : FALSE;
		if (!$saved) {
			$model = Load::Model('config');
			$saved = $model->find(NULL,array('fetch'=>'assoc'));
			if ($saved && $cache) {
				$cache->set('escher_config',$saved);
			}
		}
		if ($saved) {
			$this->saved = $saved;
			$this->settings = $this->mergeSettings(
				$this->static,
				$this->saved,
				$this->settings
			);
		}

		// Load install settings if no root found
		if (empty($this->settings['root'])) {
			$this->settings = $this->mergeSettings(
				$this->getInstallSettings(),
				$this->static,
				$this->saved,
				$this->settings
			);
		}
	}

	/**
	 * Load install settings
	 *
	 * Returns the settings required for Escher installation
	 */
	function getInstallSettings() {
		require(ESCHER_REAL_PATH.'/core/install.php');
		return array_diff_key(get_defined_vars(),array('this'=>0));
	}

	/**
	 * Merge settings
	 *
	 * Merges settings from multiple associative arrays
	 *
	 * Conflict resolution behaves in one of two ways:
	 *
	 * * Arrays are merged.
	 * * For other types, the first instance is preserved.
	 *
	 * @param string Name of the setting
	 * @param mixed Value of the setting
	 */
	protected function mergeSettings() {
		$args = array_reverse(func_get_args());
		$settings = array();
		foreach($args as $array) {
			foreach($array as $k => $v) {
				if (!is_string($k)) { continue; }
				if (array_key_exists($k,$settings)
					&& is_array($settings[$k])
					&& is_array($v)
				) {
					$settings[$k] = array_merge($v,
						array_diff_key($settings[$k],$v)
					);
				} else {
					$settings[$k] = $v;
				}
			}
		}
		return $settings;
	}

	/**
	 * ArrayAccess implementation
	 *
	 * Config helper will not allow saved or static settings to be overwritten.
	 * @param mixed Array offset
	 * @param mixed Value to be set
	 */
	function offsetSet($offset, $value) {
		if (is_string($offset) && !array_key_exists($offset,$this->static) && !array_key_exists($offset,$this->saved)) {
			$this->settings[$offset] = $value;
		}
	}

	/**
	 * ArrayAccess implementation
	 * @param mixed Array offset
	 */
	function offsetExists($offset) {
		return isset($this->settings[$offset]);
	}

	/**
	 * ArrayAccess implementation
	 * @param mixed Array offset
	 */
	function offsetUnset($offset) {
		if (is_string($offset) && !array_key_exists($offset,$this->static) && !array_key_exists($offset,$this->saved)) {
			unset($this->settings[$offset]);
		}
	}

	/**
	 * ArrayAccess implementation
	 * @param mixed Array offset
	 */
	function offsetGet($offset) {
		return isset($this->settings[$offset]) ? $this->settings[$offset] : NULL;
	}
}