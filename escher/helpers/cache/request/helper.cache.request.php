<?php

/**
 * Helper_cache_request.php
 * 
 * Request Cache Helper class
 * @author Thom Stricklin <thomshouse@gmail.com>
 * @version 1.0
 * @package Escher
 */

/**
 * Request Cache Helper class
 * @package Escher
 */
class Helper_cache_request extends Helper_cache {
	protected static $cache = array();
	
	/**
	* Null function (connect to the array, yay!)
	* @return void
	*/
	function connect() { return TRUE; }

	/**
	* Returns the value stored in the memory by its key
	*
	* @param string $key
	* @return mix
	*/
	function get($key) {
		if (is_array($key)) {
			$arr = array();
			foreach ($key as $k) {
				$arr[$k] = $this->get($k); 
			}
			return $arr;
		}
		if (array_key_exists($key,self::$cache)) {
			return self::$cache[$key];
		} else {
			return FALSE;
		}
	}

	/**
	* Store the value in the memcache memory (overwrite if key exists)
	*
	* @param string $key
	* @param mix $value
	* @param int $expire (seconds before item expires)
	* @return bool
	*/
	function set($key, $value=array(), $expire=0) {
		self::$cache[$key] = $value;
		return TRUE;
	}

	/**
	* Set the value in memcache if the value does not exist; returns FALSE if value exists
	*
	* @param sting $key
	* @param mix $value
	* @param int $expire
	* @return bool
	*/
	function add($key, $value=array(), $expire=0) {
		if (array_key_exists($key,self::$cache)) { return FALSE; }
		self::$cache[$key] = $value;
		return TRUE;
	}

	/**
	* Replace an existing value
	*
	* @param string $key
	* @param mix $value
	* @param int $expire
	* @return bool
	*/
	function replace($key, $value=array(), $expire=0, $compress=0, $usesuffix=TRUE) {
		if (!array_key_exists($key,self::$cache)) { return FALSE; }
		self::$cache[$key] = $value;
		return TRUE;
	}

	/**
	* Clear the cache
	*
	* @return void
	*/
	function flush() {
		self::$cache = array();
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
		if (is_array($key)) {
			foreach ($key as $k) {
				unset(self::$cache[$k]);
			}
			return TRUE;
		}
		if (!array_key_exists($key,self::$cache)) { return FALSE; }
		unset(self::$cache[$key]);
		return TRUE;
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
	* @return bool
	*/
	function increment($key, $count=1) {
		if (!array_key_exists($key,self::$cache)) { return FALSE; }
		self::$cache[$key] += $count;
		return TRUE;
	}

	/**
	* Decrement an existing value
	*
	* @param string $key
	* @param mix $value
	* @return bool
	*/
	function decrement($key, $count=1, $usesuffix=FALSE) {
		if (!array_key_exists($key,self::$cache)) { return FALSE; }
		self::$cache[$key] -= $count;
		return TRUE;
	}

	/**
	* Checks to see if we're connected to the static array
	* @return bool
	*/
	function isConnected() { return TRUE; }
}