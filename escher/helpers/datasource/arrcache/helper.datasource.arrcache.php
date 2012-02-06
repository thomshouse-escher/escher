<?php

/**
 * Helper_datasource_arrcache.php
 * 
 * Datasource (ArrCache) Helper class
 * @author Thom Stricklin <code@thomshouse.net>
 * @version 1.0
 * @package Escher
 */

/**
 * Datasource ArrCache Helper class
 * Note: The ArrCache uses a local array (attached to this persistent helper) to cache data in simple key-value pairs
 * @package Escher
 */
class Helper_datasource_arrcache extends Helper_datasource {
	protected $cache = array();
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
				if (!in_array($c['type'],array('unique','primary'))) {
					continue;
				}
				ksort($c['fields']);
				$keyset = array();
				foreach($c['fields'] as $k) {
					if (!isset($attrs[$k])) {
						continue 2;
					}
					$keyset[] = "{$k}={$attrs[$k]}";
				}
				$keyset = implode('&',$keyset);
				$this->cache[$m.'?'.$keyset] = $data;
			}
		}
		return true;
	}
	function get($model,$conditions=array(),$limit=1,$options=array()) {
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
		if (!array_key_exists($name,$this->cache)) {
			return false;
		}
		$result = $this->cache[$name];
		if (is_object($model) && is_object($result) && get_class($model)==get_class($result)) {
			$model->assignVars(get_object_vars($result));
		}
		return $result;
	}
	
	function delete($model) {
		if (!is_object($model)) { return false; }
		$m = $model->_m();

		// Unset all unique keys
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
			unset($this->cache[$model->_m().'?'.$keyset]);
		}
		return true;
	}
}