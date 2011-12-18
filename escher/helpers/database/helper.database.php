<?php

abstract class Helper_database extends Helper {
	public $debug = 0;
	
	abstract function connect();
	abstract function execute($sql,$vars=NULL);
	abstract function getAll($sql,$vars=NULL);
	abstract function getAssoc($sql,$vars=NULL,$force_array=FALSE);
	abstract function getCol($sql,$vars=NULL);
	abstract function getFieldNames($table);
	abstract function getSchema($table);
	abstract function getOne($sql,$vars=NULL);
	abstract function getRow($sql,$vars=NULL);
	abstract function getAutoId($sql=NULL,$vars=NULL);
	abstract function isConnected();
	abstract function q($str);
	abstract function date($ts);
	abstract function time($ts);
	
	function exec($sql,$vars) {
		$this->execute($sql,$vars);	
	}
	
	function t($table) {
		return $this->prefix.$table;
	}
}