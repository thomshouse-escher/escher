<?php

/**
 * Helper_datasource_cache.php
 * 
 * Cache Datasource Helper class
 * @author Thom Stricklin <code@thomshouse.net>
 * @version 1.0
 * @package Escher
 */

/**
 * Datasource Cache Helper class
 * @package Escher
 */
class Helper_datasource_cache extends Helper_datasource {
	protected $cache;

	function __construct($args) {
		parent::__construct($args);
		$type = $args['type'];
		unset($args['type']);
		$this->cache = Load::Helper('cache',$type,$args);
	}

	function set($model,$data=array(),$values=NULL) {
		$sets = array();
		if (is_object($model)) {
		// If object provided, set data to the object
			$m = $model->_m();
			$data = get_object_vars($model);
		} elseif (is_string($model)) {
		// Else set data to an associative array
			$m = $model;
			if (array_keys($data)==array_keys(array_values($data))) {
				$data = array_combine($data,$values);
			}
		} else { return false; }
		// Cache all unique keys
		if (is_object($model)) {
			foreach($model->_schemaKeys as $c) {
				ksort($c['fields']);
				$keyset = array();
				foreach($c['fields'] as $k) {
					if (!isset($data[$k])) {
						continue 2;
					}
					$keyset[] = "{$k}={$data[$k]}";
				}
				$keyset = implode('&',$keyset);
				$this->cache->set($m.'?'.$keyset,$data);
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
		if (!$result = $this->cache->get($name)) {
			return false;
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
			$this->cache->expire($model->_m().'?'.$keyset);
		}
		// Rinse and repeat for loaded values
		foreach($model->_schemaKeys as $c) {
			ksort($c['fields']);
			$keyset = array();
			foreach($c['fields'] as $k) {
				if (!isset($model->_savedValues[$k])) {
					continue 2;
				}
				$keyset[] = "{$k}={$model->_savedValues[$k]}";
			}
			$keyset = implode('&',$keyset);
			$this->cache->expire($model->_m().'?'.$keyset);
		}
		return true;
	}
}