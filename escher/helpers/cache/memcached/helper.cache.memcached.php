<?php

/**
 * Helper_cache_memcache.php
 * 
 * Memcache Cache Helper class
 * @author Andrew Detwiler <adetwiler@adidamnetworks.com>
 * @version 1.0
 * @package Escher
 */

/**
 * Memcached Cache Helper class
 * @package Escher
 */
class Helper_cache_memcached extends Helper_cache {
	protected $memcache;
	var $debug = 0;
	
	/**
	* Connects to the memcached server
	* @return void
	*/
	function connect() {
		if (is_null($this->memcache)) {
			$this->memcache = new Memcache;
			set_error_handler('exception_error_handler');
			try {
				$this->memcache->connect($this->host,$this->port);
			} catch (Exception $e) {
				$this->memcache = FALSE;
			}
			restore_error_handler();
			if ($this->memcache===FALSE) {
				return FALSE;
			}
		} elseif ($this->memcache===FALSE) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	* Returns the value stored in the memory by it's key
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
				$arr[$k] = unserialize($this->memcache->get($pk));
				$this->debug('GET',$pk);
			}
			return $arr;
		}
		$key = $this->k($key);
		$this->debug('GET',$key);
		return unserialize($this->memcache->get($key));
	}

	/**
	* Store the value in the memcache memory (overwrite if key exists)
	*
	* @param string $key
	* @param mix $value
	* @param int $expire (seconds before item expires)
	* @param bool $compress
	* @return bool
	*/
	function set($key, $value=array(), $expire=0, $compress=0) {
		if (!$this->connect()) { return FALSE; }
		$key = $this->k($key);
		$this->debug('SET',$key,$value,$expire);
		return $this->memcache->set($key, serialize($value), $compress?MEMCACHE_COMPRESSED:null, $expire);
	}

	/**
	* Set the value in memcache if the value does not exist; returns FALSE if value exists
	*
	* @param sting $key
	* @param mix $value
	* @param int $expire
	* @param bool $compress
	* @return bool
	*/
	function add($key, $value=array(), $expire=0, $compress=0) {
		if (!$this->connect()) { return FALSE; }
		$key = $this->k($key);
		$this->debug('ADD',$key,$value,$expire);
		return $this->memcache->add($key, serialize($value), $compress?MEMCACHE_COMPRESSED:null, $expire);
	}

	/**
	* Replace an existing value
	*
	* @param string $key
	* @param mix $value
	* @param int $expire
	* @param bool $compress
	* @return bool
	*/
	function replace($key, $value=array(), $expire=0, $compress=0) {
		if (!$this->connect()) { return FALSE; }
		$key = $this->k($key);
		$this->debug('REPLACE',$key,$value,$expire);
		return $this->memcache->replace($key, $value, $compress?MEMCACHE_COMPRESSED:null, $expire);
	}

	/**
	* Clear the cache
	*
	* @return void
	*/
	function flush() {
		if (!$this->connect()) { return FALSE; }
		$this->debug('FLUSH');
		$this->memcache->flush();
	}

	/**
	* Expire a record or set a timeout
	*
	* @param string $key
	* @param int $timeout
	* @return bool
	*/
	function expire($key, $timeout=0) {
		if (!$this->connect()) { return FALSE; }
		if (is_array($key)) {
			$result = TRUE;
			foreach ($key as $k) {
				$k = $this->k($k);
				if(!$this->memcache->delete($k, $timeout)) {
					$result = FALSE;
				}
			}
			return $result;
		}
		$key = $this->k($key);
		$this->debug('EXPIRE',$key,NULL,$timeout);
		return $this->memcache->delete($key, $timeout);
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
		return $this->memcache->increment($key, $count);
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
		return $this->memcache->decrement($key, $count);
	}

	/**
	* Checks if memcache object is null
	* @return bool
	*/
	function isConnected() {
		return (bool)$this->memcache;
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
		echo "<hr />(memcache): $command";
		if (!is_null($key)) { echo " `$key`"; }
		if (!is_null($value)) { echo " = '".serialize($value)."'"; }
		if (!is_null($until)) { echo " UNTIL $until"; }
		echo '<hr />';
		if (is_int($this->debug) && $this->debug>1) {
			echo '<pre>'; debug_print_backtrace(); echo '</pre>';
		}
	}
}