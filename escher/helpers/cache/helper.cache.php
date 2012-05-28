<?php

/**
 * Helper_cache.php
 * 
 * Cache Helper base class
 * @author Andrew Detwiler <adetwiler@adidamnetworks.com>
 * @version 1.0
 * @package Escher
 */

/**
 * Cache Helper base class
 * @package Escher
 */
abstract class Helper_cache extends Helper {
	protected $prefix;
	protected $host;
	protected $port;

	abstract function connect();
	abstract function get($key);
	abstract function set($key, $value=array(), $expire=0);
	abstract function add($key, $value=array(), $expire=0);
	abstract function replace($key, $value=array(), $expire=0);
	abstract function flush();
	abstract function expire($key, $timeout=0);
	abstract function increment($key, $count=1);
	abstract function decrement($key, $count=1);
	abstract function isConnected();
	
	function __construct($args) {
		parent::__construct($args);
		if (empty($this->prefix)) {
			$CFG = Load::Config();
			$this->prefix = isset($CFG['instance'])
				? $CFG['instance']
				: md5($CFG['wwwroot']);
		}
	}

	/**
	* Prepends the key with the prefix
	*
	* @param string $key
	* @return string
	*/
	final public function k($key) {
		return $this->prefix.':'.$key;
	}
}