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
		// Cache the id if it is provided in the data (or in $options)
		if (!empty($attrs['id'])) {
			$this->memcache->set($m.'_id_'.$attrs['id'],$data);
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
				$this->memcache->set($m.'_'.$keyset,$data);
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
		$name = $m.'_';
		if (is_array($conditions)) {
			ksort($conditions);
			$c = array();
			foreach($conditions as $k => $v) {
				$c[] = $k.'_'.$v;
			}
			$name .= implode('_',$c);
		} else { return false; }
		if (!$result = $this->memcache->get($name)) {
			return false;
		}
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
		$this->memcache->expire($m.'_id_'.$id);
		// Unset the other keys, if provided and valid
		if (is_object($model)) {
			foreach($model->_cache_keys() as $c) {
				ksort($c);
				$keyset = array();
				foreach($c as $k) {
					if (!isset($model->$k)) {
						continue 2;
					}
					$keyset[] = "{$k}_{$model->$k}";
				}
				$keyset = implode('_',$keyset);
				$this->memcache->expire($m.'_'.$keyset);
			}
		}
		return true;
	}
}