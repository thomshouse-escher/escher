<?php

/**
 * Helper_cache_apc.php
 * 
 * APC Cache Helper class
 * @author Thom Stricklin <thomshouse@gmail.com>
 * @version 1.0
 * @package Escher
 */

/**
 * APC Cache Helper class
 * @package Escher
 */
class Helper_cache_apc extends Helper_cache {
	protected $available;
	protected $slamDefense;
	protected $addFunction;
	var $debug = 0;
	
	/**
	* Checks APC availability
	* @return void
	*/
	function connect() {
		if (is_null($this->available)) {
			$this->available = function_exists('apc_store');
		}
		return $this->available;
	}

	/**
	* Returns the value stored in APC by its key
	*
	* @param string $key
	* @return mix
	*/
	function get($key) {
		if (!$this->connect()) { return FALSE; }
		if (is_array($key)) {
			$arr = array();
			foreach ($key as $k) {
				$pk = $this->k($k);
				$arr[$k] = unserialize(apc_fetch($pk));
				$this->debug('GET',$pk);
			}
			return $arr;
		}
		$key = $this->k($key);
		$this->debug('GET',$key);
		return unserialize(apc_fetch($key));
	}

	/**
	* Store the value in APC (overwrite if key exists)
	*
	* @param string $key
	* @param mix $value
	* @param int $expire (seconds before item expires)
	* @return bool
	*/
	function set($key, $value=array(), $expire=0) {
		if (!$this->connect()) { return FALSE; }
		$key = $this->k($key);
		$this->debug('SET',$key,$value,$expire);
		$this->checkSlamDefense($key);
		return apc_store($key,serialize($value),$expire);
	}

	/**
	* Set the value in APC if the value does not existor return FALSE
	*
	* @param sting $key
	* @param mix $value
	* @param int $expire
	* @return bool
	*/
	function add($key, $value=array(), $expire=0) {
		if (!$this->connect()) { return FALSE; }
		$key = $this->k($key);
		$this->debug('ADD',$key,$value,$expire);
		if (is_null($this->addFunction)) {
			$this->addFunction = function_exists('apc_add');
		}
		if ($this->addFunction) {
			return apc_add($key,serialize($value),$expire);
		}
		if (apc_exists($key)) { return FALSE; }
		$this->checkSlamDefense($key);
		return apc_store($key,serialize($value),$expire);
	}

	/**
	* Replace an existing value
	*
	* @param string $key
	* @param mix $value
	* @param int $expire
	* @return bool
	*/
	function replace($key, $value=array(), $expire=0) {
		if (!$this->connect()) { return FALSE; }
		$key = $this->k($key);
		$this->debug('REPLACE',$key,$value,$expire);
		if (!apc_exists($key)) { return FALSE; }
		$this->checkSlamDefense($key);
		return apc_store($key,serialize($value),$expire);
	}

	/**
	* Clear the cache
	*
	* @return void
	*/
	function flush() {
		if (!$this->connect()) { return FALSE; }
		return apc_clear_cache('user');
	}

	/**
	* Expire a record or set a timeout
	*
	* @param string $key
	* @param int $timeout
	* @return bool
	*/
	function expire($key, $timeout=0) {
		if ($timeout!=0) { return FALSE; }
		if (!$this->connect()) { return FALSE; }
		if (is_array($key)) {
			$result = TRUE;
			foreach ($key as $k) {
				$k = $this->k($k);
				if(!apc_delete($k)) {
					$result = FALSE;
				}
			}
			return $result;
		}
		$key = $this->k($key);
		$this->debug('EXPIRE',$key,NULL,$timeout);
		return apc_delete($key);
	}

	/**
	* Delete a record immediately
	*
	* @param string $key
	* @return bool
	*/
	function delete($key) {
		return $this->expire($key,0);
	}

	/**
	* Increment an existing integer value
	*
	* @param string $key
	* @param mix $value
	* @return bool
	*/
	function increment($key, $count=1) {
		if (!$this->connect()) { return FALSE; }
		$key = $this->k($key);
		$this->debug('INCREMENT',$key,$count);
		return apc_inc($key, $count);
	}

	/**
	* Decrement an existing value
	*
	* @param string $key
	* @param mix $value
	* @return bool
	*/
	function decrement($key, $count=1) {
		if (!$this->connect()) { return FALSE; }
		$key = $this->k($key);
		$this->debug('DECREMENT',$key,$count);
		return apc_dec($key, $count);
	}

	/**
	* Checks if memcache object is null
	* @return bool
	*/
	function isConnected() {
		return (bool)$this->available;
	}

	/**
	* Expires keys in the event that apc.slam_defense is on
	*
	* @param string $key
	*/
	protected function checkSlamDefense($key) {
		if (is_null($this->slamDefense)) {
			$this->slamDefense = (bool)ini_get('apc.slam_defense');
		}
		if ($this->slamDefense) {
			apc_delete($key);
		}
	}

	/**
	* Debug reporting
	*
	* @param string $command
	* @param string $key
	* @param mix $value
	* @param int $until
	*/
	protected function debug($command,$key=NULL,$value=NULL,$until=NULL) {
		if (!$this->debug) { return; }
		echo "<hr />(apc): $command";
		if (!is_null($key)) { echo " `$key`"; }
		if (!is_null($value)) { echo " = '".serialize($value)."'"; }
		if (!is_null($until)) { echo " UNTIL $until"; }
		echo '<hr />';
		if (is_int($this->debug) && $this->debug>1) {
			echo '<pre>'; debug_print_backtrace(); echo '</pre>';
		}
	}
}