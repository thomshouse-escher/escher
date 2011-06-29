<?php

/**
 * Helper_cache_memcache.php
 * 
 * Cache (Memcache) Helper class
 * @author Andrew Detwiler <adetwiler@adidamnetworks.com>
 * @version 1.0
 * @package Escher
 */

/**
 * Cache Memcache Helper class
 * @package Escher
 */
class Helper_cache_memcache extends Helper_cache {
	var $memcache;
	
	/**
	* Connects to the memcached server
	* @return void
	*/
	function connect() {
		if (!$this->isConnected()) {
			$this->memcache = new Memcache;
			@$this->memcache->connect($this->host,$this->port);
			$this->getSuffix();
		}
	}

	/**
	* Returns the value stored in the memory by it's key
	*
	* @param string $key
	* @param bool $usesuffix
	* @return mix
	*/
	function get($key, $usesuffix=TRUE) {
		$this->connect();
		if (is_array($key)) {
			$arr = array();
			foreach ($key as $k) {
				$ks = $this->k($k, $usesuffix);
				$arr[$k] = unserialize($this->memcache->get($ks)); 
			}
			return $arr;
		}
		$key = $this->k($key, $usesuffix);
		return unserialize($this->memcache->get($key));
	}

	/**
	* Store the value in the memcache memory (overwrite if key exists)
	*
	* @param string $key
	* @param mix $value
	* @param int $expire (seconds before item expires)
	* @param bool $compress
	* @param bool $usesuffix
	* @return bool
	*/
	function set($key, $value=array(), $expire=0, $compress=0, $usesuffix=TRUE) {
		$this->connect();
		$key = $this->k($key, $usesuffix);
		return $this->memcache->set($key, serialize($value), $compress?MEMCACHE_COMPRESSED:null, $expire);
	}

	/**
	* Set the value in memcache if the value does not exist; returns FALSE if value exists
	*
	* @param sting $key
	* @param mix $value
	* @param int $expire
	* @param bool $compress
	* @param bool $usesuffix
	* @return bool
	*/
	function add($key, $value=array(), $expire=0, $compress=0, $usesuffix=TRUE) {
		$this->connect();
		$key = $this->k($key, $usesuffix);
		return $this->memcache->add($key, serialize($value), $compress?MEMCACHE_COMPRESSED:null, $expire);
	}

	/**
	* Replace an existing value
	*
	* @param string $key
	* @param mix $value
	* @param int $expire
	* @param bool $compress
	* @param bool $usesuffix
	* @return bool
	*/
	function replace($key, $value=array(), $expire=0, $compress=0, $usesuffix=TRUE) {
		$this->connect();
		$key = $this->k($key, $usesuffix);
		return $this->memcache->replace($key, $value, $compress?MEMCACHE_COMPRESSED:null, $expire);
	}

	/**
	* Clear the cache
	*
	* @return void
	*/
	function flush() {
		$this->connect();
		$this->memcache->flush();
	}

	/**
	* Expire a record or set a timeout
	*
	* @param string $key
	* @param int $timeout
	* @param bool $usesuffix
	* @return bool
	*/
	function expire($key, $timeout=0, $usesuffix=TRUE) {
		$this->connect();
		if (is_array($key)) {
			foreach ($key as $k) {
				$k = $this->k($k, $usesuffix);
				if(!$this->memcache->delete($k, $timeout)) {
					return false;
				}
			}
			return true;
		}
		$key = $this->k($key, $usesuffix);
		return $this->memcache->delete($key, $timeout);
	}

	/**
	* Delete a record immediately
	*
	* @param string $key
	* @param bool $usesuffix
	* @return bool
	*/
	function delete($key, $usesuffix=TRUE) {
		return $this->expire($key,0,$usesuffix);
	}

	/**
	* Increment an existing integer value
	*
	* @param string $key
	* @param mix $value
	* @param bool $usesuffix
	* @return bool
	*/
	function increment($key, $count=1, $usesuffix=FALSE) {
		$this->connect();
		$key = $this->k($key, $usesuffix);
		return $this->memcache->increment($key, $count);
	}

	/**
	* Decrement an existing value
	*
	* @param string $key
	* @param mix $value
	* @param bool $usesuffix
	* @return bool
	*/
	function decrement($key, $count=1, $usesuffix=FALSE) {
		$this->connect();
		$key = $this->k($key, $usesuffix);
		return $this->memcache->decrement($key, $count);
	}

	/**
	* Checks if memcache object is null
	* @return bool
	*/
	function isConnected() {
		return !is_null($this->memcache);
	}
}