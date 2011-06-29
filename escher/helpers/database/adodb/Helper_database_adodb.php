<?php

class Helper_database_adodb extends Helper_database {
	var $adodb;
	
	function __construct($args) {
		parent::__construct($args);
	}
	
	function connect() {
		if (!$this->isConnected()) {
			Load::lib('adodb/adodb.inc.php');
			$this->adodb = &ADONewConnection($this->adotype);
			if ($result = $this->adodb->Connect($this->address, $this->username, $this->password, $this->database)) {
				$this->adodb->SetFetchMode(ADODB_FETCH_ASSOC);
				$this->adodb->Execute('SET collation_connection="utf8_general_ci", collation_server="utf8_general_ci", character_set_client="utf8",
					character_set_connection="utf8", character_set_results="utf8", character_set_server="utf8"');
			}
		}
		$this->adodb->debug = $this->debug;
	}
	
	function execute($sql,$vars=NULL) {
		$this->connect();
		return $this->adodb->Execute($sql,$vars);
	}
	
	function insert($table,$data,$keys=NULL,$ignore=FALSE) {
		return $this->insertOrReplace($table,$data,$keys,$ignore);
	}

	function insertOrReplace($table,$data,$keys=NULL,$ignore=FALSE,$replace=FALSE) {
		$this->connect();
		// If $data is not multidimensional, force it to be
		if (!is_array($data)) {
			$data = array((array)$data);
		} elseif (!array_key_exists(0,$data)) {
			$dkeys = array_keys($data);
			if (is_array($data[$dkeys[0]])) {
				$data = array_values($data);
			} else {
				$data = array($data);
			}
		}
		if (!is_array($data[0])) {
			foreach($data as $k => $v) {
				$data[$k] = array($v);
			}
		}
		
		// If $keys is not set, try to extract it from $data
		if (is_null($keys)) {
			// But if $data[0] is not associative, return false;
			if ($data[0]==array_values($data[0])) {
				return false;
			}
			$keys = array_keys($data[0]);
		}
		// Make sure $keys is not associative
		$keys = array_values($keys);
		
		$values = array();

		foreach($data as $row) {
			$vrow = array();
			if ($row==array_values($row)) {
				foreach($row as $r) {
					$vrow[] = $this->adodb->qstr($r);
				}
			} else {
				foreach($keys as $k) {
					$vrow[] = @$this->adodb->qstr(@$row[$k]);
				}
			}
			$values[] = '('.implode(',',$vrow).')';
		}
		
		$keys_sql = array();
		foreach($keys as $k) {
			$keys_sql[] = addslashes($k);	
		}
		
		if ($replace) {
			$sql = 'REPLACE'.($ignore ? ' IGNORE':'').' INTO '.$this->t($table).' ('.implode(',',$keys_sql).') VALUES '.implode(',',$values);
		} else {
			$sql = 'INSERT'.($ignore ? ' IGNORE':'').' INTO '.$this->t($table).' ('.implode(',',$keys_sql).') VALUES '.implode(',',$values);
		}

		return $this->adodb->Execute($sql);
	}

	function replace($table,$data,$keys=NULL) {
		return $this->insertOrReplace($table,$data,$keys,FALSE,TRUE);
	}
	
	function affectedRows() {
		if (!$this->isConnected()) {
			return false;
		}
		return $this->adodb->Affected_Rows();
	}
	
	function lastID() {
		if (!$this->isConnected()) {
			return false;
		}
		return $this->adodb->Insert_ID();
	}
	
	function getAll($sql,$vars=NULL) {
		$this->connect();
		return $this->adodb->GetAll($sql,$vars);
	}
	
	function getAssoc($sql,$vars=NULL,$force_array=FALSE) {
		$this->connect();
		return $this->adodb->GetAssoc($sql,$vars,$force_array);
	}
	
	function getCol($sql,$vars=NULL) {
		$this->connect();
		return $this->adodb->GetCol($sql,$vars);
	}
	
	function getFieldNames($table) {
		$this->connect();
		return $this->adodb->MetaColumnNames($this->t($table));
	}
	
	function getSchema($table) {
		$this->connect();
		$columns = $this->adodb->MetaColumns($this->t($table));
		$result = array();
		if (is_array($columns)) {
			foreach($columns as $c) {
				$result[$c->name] = $c->type;
			}
		}
		return $result;
	}
	
	function getOne($sql,$vars=NULL) {
		$this->connect();
		return $this->adodb->GetOne($sql,$vars);
	}
	
	function getRow($sql,$vars=NULL) {
		$this->connect();
		return $this->adodb->GetRow($sql,$vars);
	}
	
	function getAutoId($sql=NULL,$vars=NULL) {
		$this->connect();
		return $this->adodb->Insert_ID();
	}

	function isConnected() {
		return !is_null($this->adodb);
	}
	
	function q($str) {
		$this->connect();
		return $this->adodb->qstr($str);
	}
	
	function date($ts) {
		$this->connect();
		return $this->adodb->BindDate($ts);
	}

	function time($ts) {
		$this->connect();
		return $this->adodb->BindTimeStamp($ts);
	}
}