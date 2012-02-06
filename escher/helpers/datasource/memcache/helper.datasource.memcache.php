<?php

/**
 * Helper_datasource_memcache.php
 * 
 * Datasource (ArrCache) Helper class
 * @author Thom Stricklin <code@thomshouse.net>
 * @version 1.0
 * @package Escher
 */

/**
 * Datasource Memcache Helper class
 * @package Escher
 */
class Helper_datasource_memcache extends Helper_datasource {
	protected $memcache;

	function __construct($args) {
		parent::__construct($args);
		$this->memcache = Load::Helper('cache','memcache',$args);
	}

	function set($model,$attrs=array(),$values=NULL) {
		$sets = array();
		if (is_object($model)) {
		// If object provided, set data to the object
			$m = $model->_m();
			$data = $model;
			$attrs = get_object_vars($model);
		} elseif (is_string($model)) {
		// Else set data to an associative array
			$m = $model;
			if (array_keys($attrs)==array_keys(array_values($attrs))) {
				$attrs = array_combine($attrs,$values);
			}
			$data = $attrs;
		} else { return false; }
		// Cache all unique keys
		if (is_object($model)) {
			foreach($model->_schemaKeys as $c) {
				ksort($c['fields']);
				$keyset = array();
				foreach($c['fields'] as $k) {
					if (!isset($attrs[$k])) {
						continue 2;
					}
					$keyset[] = "{$k}={$attrs[$k]}";
				}
				$keyset = implode('&',$keyset);
				$this->memcache->set($m.'?'.$keyset,$data);
			}
		}
		return true;
	}
	function get(&$model,$conditions=array(),$limit=1,$options=array()) {
		if (is_object($model)) {
			$m = $model->_m();
		} elseif (is_string($model)) {
			$m = $model;
		} else { return false; }
		$name = $m.'?';
		if (is_array($conditions)) {
			ksort($conditions);
			$c = array();
			foreach($conditions as $k => $v) {
				$c[] = $k.'='.$v;
			}
			$name .= implode('&',$c);
		} else { return false; }
		if (!$result = $this->memcache->get($name)) {
			return false;
		}
		if (is_object($model) && is_object($result) && get_class($model)==get_class($result)) {
				$model->assignVars(get_object_vars($result));
		}
		return $result;
	}
	
	function delete($model) {
		if (!is_a($model,'Model')) { return false; }

		// Unset all unique keys
		foreach($model->_schemaKeys as $c) {
			ksort($c['fields']);
			$keyset = array();
			foreach($c['fields'] as $k) {
				if (!isset($model->$k)) {
					continue 2;
				}
				$keyset[] = "{$k}={$model->$k}";
			}
			$keyset = implode('&',$keyset);
			$this->memcache->expire($model->_m().'?'.$keyset);
		}
		return true;
	}
}