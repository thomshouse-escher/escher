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
		// Cache the id if it is provided in the data (or in $options)
		if (!empty($attrs['id'])) {
			$this->cache[$m.'_id_'.$attrs['id']] = $data;
		}
		// Cache the other keys, if provided and valid
		if (is_object($model)) {
			foreach($model->_cache_keys() as $c) {
				ksort($c);
				$keyset = array();
				foreach($c as $k) {
					if (!isset($attrs[$k])) {
						continue 2;
					}
					$keyset[] = "{$k}_{$attrs[$k]}";
				}
				$keyset = implode('_',$keyset);
				$this->cache[$m.'_'.$keyset] = $data;
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
		$name = $m.'_';
		if (is_array($conditions)) {
			ksort($conditions);
			$c = array();
			foreach($conditions as $k => $v) {
				$c[] = $k.'_'.$v;
			}
			$name .= implode('_',$c);
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
	
	function delete($model,$id=NULL) {
		if (is_object($model)) {
			$id = $model->id;
			$m = $model->_m();
		} elseif (is_string($model)) {
			$m = $model;
		} else { return false; }
		if (is_null($id)) {
			return false;
		}
		// Unset the id
		unset($this->cache[$m.'_id_'.$attrs['id']]);
		// Unset the other keys, if provided and valid
		if (is_object($model)) {
			foreach($model->_cache_keys() as $c) {
				ksort($c);
				$keyset = array();
				foreach($c as $k) {
					if (!isset($attrs[$k])) {
						continue 2;
					}
					$keyset[] = "{$k}_{$attrs[$k]}";
				}
				$keyset = implode('_',$keyset);
				unset($this->cache[$m.'_'.$keyset]);
			}
		}
		return true;
	}
}