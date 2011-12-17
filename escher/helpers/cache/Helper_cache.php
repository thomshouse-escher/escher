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
	protected $suffix;
	protected $host;
	protected $port;

	abstract function connect();
	abstract function get($key, $usesuffix=TRUE);
	abstract function set($key, $value=array(), $expire=0, $compress=0, $usesuffix=TRUE);
	abstract function add($key, $value=array(), $expire=0, $compress=0, $usesuffix=TRUE);
	abstract function replace($key, $value=array(), $expire=0, $compress=0, $usesuffix=TRUE);
	abstract function flush();
	abstract function expire($key, $timeout=0, $usesuffix=TRUE);
	abstract function increment($key, $count=1, $usesuffix=FALSE);
	abstract function decrement($key, $count=1, $usesuffix=FALSE);
	abstract function isConnected();
	
	function __construct($args) {
		parent::__construct($args);
		if (empty($this->prefix)) {
			$CFG = Load::Config();
			$this->prefix = md5($CFG['wwwroot']);
		}
	}

	/**
	* Set the keys prefix and suffix
	*
	* @param string $key
	* @param bool $usesuffix
	* @return string
	*/
	final public function k($key, $usesuffix=TRUE) {
		$k = $this->prefix.':'.$key;
		if ($usesuffix && !empty($this->suffix)) {
			$k .= '('.$this->suffix.')';
		}
		return $k;
	}

	/**
	* Initalizes the cache key suffix 
	*
	* @return void
	*/
	function getSuffix() {
		$CFG = Load::Config();
		$suffix = $this->get('cache.internal.keysuffix', FALSE);
		if(empty($suffix)) {
			$suffix = date('ymdhisa');
			$this->set('cache.internal.keysuffix',$suffix,0, 0, FALSE);
		}
		$this->suffix = $suffix;
	}
}