<?php

/**
 * Helper_datasource.php
 * 
 * Datasource Helper base class
 * @author Thom Stricklin <code@thomshouse.net>
 * @version 1.0
 * @package Escher
 */

/**
 * Datasource Helper base class
 * @package Escher
 */
abstract class Helper_datasource extends Helper {
	function set($model,$attrs=array(),$values=NULL) { }
	function get($model,$params=array()) { }
	function delete($model,$key) { }
	
	function date($ts) { return $ts; }
	function time($ts) { return $ts; }
}