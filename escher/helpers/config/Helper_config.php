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
abstract class Helper_config extends Helper implements ArrayAccess {
	/**
	 * Configuration settings from the database/cache
	 * @var array
	 */
	protected $saved;
	/**
	 * All configuration settings
	 * @var array
	 */
	protected $settings;
	/**
	 * Configuration settings from defaults and config.php
	 * @var array
	 */
	protected $static;

	/**
	 * Config constructor
	 *
	 * Loads the defaults.php file, which handles loading of config.php and
	 * merging/reconciliation of default and specified values.
	 */
	function __construct() {
		include(ESCHER_FILE_PATH.'/defaults.php');
		$this->static = get_defined_vars();
		$this->settings = $this->static;
	}

	/**
	 * Save a setting
	 *
	 * Saves an individual config setting to the database.
	 *
	 * If the setting is present in $this->static and is not an array,
	 * the operation will not occur.
	 *
	 * @param string Name of the setting
	 * @param mixed Value of the setting
	 */
	function save($name,$value) {
		if (is_string($name) && !is_array($value) &&
			!array_key_exists($name,$this->static)
		) {
			$db = Load::DB();
			$cache = Load::Cache();
			if (is_scalar($value)) {
				$db->replace('escher_config',
					array('name'=>$name,'value'=>$value,'serialized'=>0)
				);
			} else {
				$db->replace('escher_config',
					array('name'=>$name,'value'=>serialize($value),'serialized'=>1)
				);
			}
			if (is_object($cache)) {
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
		$cache = Load::Cache();
		$saved = is_object($cache) ? $cache->get('escher_config') : FALSE;
		if (!$saved) {
			$db = Load::DB();
			$result = $db->getAssoc(
				'SELECT * FROM '.$db->t('escher_config').' ORDER BY name ASC'
			);
			if (!empty($result)) {
				$saved = array();
				foreach($result as $k => $r) {
					if ($r['serialized']) {
						$saved[$k] = unserialize($r['value']);
					} else {
						$saved[$k] = $r['value'];
					}
				}
				if (is_object($cache)) {
					$cache->set('escher_config',$saved);
				}
			}
		}
		if ($saved) {
			$this->saved = $saved;
			$this->settings =
				$this->mergeSettings($this->settings,$this->saved,$this->static);
		}
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
		$args = func_get_args();
		$settings = array();
		foreach($args as $array) {
			foreach($array as $k => $v) {
				if (!is_string($k)) { continue; }
				if (array_key_exists($k,$settings) && is_array($v)) {
					$settings[$k] =
						array_merge($v,array_diff_key($settings[$k],$v));
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