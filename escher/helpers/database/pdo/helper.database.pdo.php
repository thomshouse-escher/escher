<?php

class Helper_database_pdo extends Helper_database {
	protected static $schemas = array();
	protected $driver = 'mysql';
	protected $host = '127.0.0.1';
	protected $port;
	protected $database;
	protected $username;
	protected $password;
	protected $db;
	protected $statement;
	var $debug = FALSE;
	
	function __construct($args) {
		parent::__construct($args);
	}
	
	function connect() {
		if (!$this->isConnected()) {
			try {
				$this->db = new PDO(
					"{$this->driver}:host={$this->host};".(!empty($this->port) ? "port={$this->port};" : '')."dbname={$this->database}",
					$this->username,$this->password);
			} catch(PDOException $e) {
				$this->db = NULL;
			}
		}
		return !is_null($this->db);
	}

	function disconnect() {
		$this->db = NULL;
	}
	
	function execute($sql,$vars=NULL) {
		if (!$this->connect()) { return FALSE; }
		$this->statement = $this->db->prepare($sql);
		$result = $this->statement->execute($vars);
		$this->debug($sql,$vars,$this->statement);
		return $result;
	}
	
	function insert($table,$data,$keys=NULL,$ignore=FALSE) {
		return $this->insertOrReplace($table,$data,$keys,$ignore);
	}

	function insertOrReplace($table,$data,$keys=NULL,$ignore=FALSE,$replace=FALSE) {
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
		$vrows = array();

		foreach($data as $row) {
			if ($row==array_values($row)) {
				$vrow = array_fill(0,sizeof($row),'?');
				foreach($row as $r) {
					$values[] = $r;
				}
			} else {
				$vrow = array_fill(0,sizeof($keys),'?');
				foreach($keys as $k) {
					$values[] = !empty($row[$k]) ? $row[$k] : NULL;
				}
			}
			$vrows[] = '('.implode(',',$vrow).')';
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

		if (!$this->connect()) { return FALSE; }
		$this->statement = $this->db->prepare($sql);
		$result = $this->statement->execute($values);
		$this->debug($sql,$values,$this->statement);
		return $result;
	}

	function replace($table,$data,$keys=NULL) {
		return $this->insertOrReplace($table,$data,$keys,FALSE,TRUE);
	}
	
	function affectedRows() {
		if (!$this->isConnected()) { return FALSE; }
		return $this->statement->rowCount();
	}
	
	function lastID() {
		if (!$this->isConnected()) { return FALSE; }
		return $this->db->lastInsertId();
	}
	
	function getAll($sql,$vars=NULL) {
		if (!$this->connect()) { return FALSE; }
		$this->statement = $this->db->prepare($sql);
		$this->statement->execute($vars);
		$this->debug($sql,$vars,$this->statement);
		return $this->statement->fetchAll(PDO::FETCH_ASSOC);
	}
	
	function getAssoc($sql,$vars=NULL,$force_array=FALSE) {
		if (!$this->connect()) { return FALSE; }
		$this->statement = $this->db->prepare($sql);
		$this->statement->execute($vars);
		$this->debug($sql,$vars,$this->statement);
		if (!$dbresult = $this->statement->fetchAll(PDO::FETCH_ASSOC)) {
			return array();
		}
		$result = array();
		if (!$force_array && sizeof($dbresult[0])==2) {
			foreach($dbresult as $r) {
				$rkey = array_shift($r);
				$result[$rkey] = array_shift($r);
			}
		} else {
			foreach($dbresult as $r) {
				$rkey = array_shift($r);
				$result[$rkey] = $r;
			}
		}
		return $result;
	}
	
	function getCol($sql,$vars=NULL) {
		if (!$this->connect()) { return FALSE; }
		$this->statement = $this->db->prepare($sql);
		$this->statement->execute($vars);
		$this->debug($sql,$vars,$this->statement);
		return $this->statement->fetchAll(PDO::FETCH_COLUMN, 0);
	}
	
	function getFieldNames($table) {
		return array_keys($this->getSchema);
	}
	
	function getOne($sql,$vars=NULL) {
		if (!$this->connect()) { return FALSE; }
		$this->statement = $this->db->prepare($sql);
		$this->statement->execute($vars);
		$this->debug($sql,$vars,$this->statement);
		return $this->statement->fetchColumn(0);
	}
	
	function getRow($sql,$vars=NULL) {
		if (!$this->connect()) { return FALSE; }
		$this->statement = $this->db->prepare($sql);
		$this->statement->execute($vars);
		$this->debug($sql,$vars,$this->statement);
		return $this->statement->fetch(PDO::FETCH_ASSOC);
	}
	
	function getAutoId($sql=NULL,$vars=NULL) {
		if (!$this->isConnected()) { return FALSE; }
		return $this->db->lastInsertId();
	}

	function isConnected() {
		return !is_null($this->db);
	}

	function n($str) {
		switch ($this->driver) {
			case 'mysql': $ld=$rd='`'; break;
			case 'postgresql':
			case 'sqlite': $ld=$rd='"'; break;
			default: $ld=$rd=''; break;
		}
		return preg_replace(
			array('/[^\w_.*0-9]/','/([\w0-9_]+)/'),
			array('',$ld.'$1'.$rd),
			$str
		);
	}

	function q($str) {
		// PDO doesn't handle NULLs properly
		if (is_null($str)) { return "NULL"; }
		if (!$this->connect()) { return FALSE; }
		return $this->db->quote($str);
	}

	function t($str,$q=TRUE) {
		if ($q) { return $this->n(parent::t($str)); }
		return parent::t($str);
	}

	function date($ts) {
		if (is_string($ts)) {
			if (is_numeric($ts) && !preg_match('/[^\d-]/',$ts)) {
				$ts = (int)$ts;
			} else {
				$ts = strtotime($ts);
			}
		}
		if (!is_int($ts)) {
			return false;
		}
		return date("Y-m-d",$ts);
	}

	function time($ts) {
		if (is_string($ts)) {
			if (is_numeric($ts) && !preg_match('/[^\d-]/',$ts)) {
				$ts = (int)$ts;
			} else {
				$ts = strtotime($ts);
			}
		}
		if (!is_int($ts)) {
			return false;
		}
		return date("Y-m-d H:i:s",$ts);
	}

	function getSchema($table) {
		if (array_key_exists($table,self::$schemas)) {
			return self::$schemas[$table];
		}
		if ($cache = Load::Cache()) {
			if ($schema = $cache->get("PDO:{$this->driver}:tableSchema:$table")) {
				return $schema;
			}
		}
		if (!$driver = $this->getDriver()) { return false; }
		if (!$schema = $driver->getSchema($table)) { return false; }
		self::$schemas[$table] = $schema;
		if ($cache) {
			$cache->set("PDO:{$this->driver}:tableSchema:$table",$schema);
		}
		return $schema;
	}

	function setSchema($table,$schema,$complete=FALSE) {
		$oldSchema = $this->getSchema($table);
		$newSchema = $schema;
		// Skip the set if schema hasn't changed
		ksort($oldSchema['fields']);
		ksort($newSchema['fields']);
		ksort($oldSchema['keys']);
		ksort($newSchema['keys']);
		if (md5(json_encode($oldSchema['fields']))
			== md5(json_encode($newSchema['fields']))
			&& md5(json_encode($oldSchema['keys']))
			== md5(json_encode($newSchema['keys']))
		) {
			return array('fields' => array(), 'keys' => array());
		}
		if (!$driver = $this->getDriver()) { return false; }
		self::$schemas[$table] = $schema;
		if ($cache = Load::Cache()) {
			$cache->set("PDO:{$this->driver}:tableSchema:$table",$schema);
		}
		return $driver->setSchema($table,$schema,$oldSchema,$complete);
	}

	protected function debug($sql,$vars,$statement) {
		if (!$this->debug) { return; }
		$qc = substr_count($sql,'?');
		if ($qc!=sizeof($vars)) {
			echo "<div>Input array does not match ?: $sql</div>";
		}
		for ($i=0; $i<$qc; $i++) {
			$rep = array_key_exists($i,$vars) ? $this->q($vars[$i]) : '';
			$sql = preg_replace('/\?/',$rep,$sql,1);
		}
		echo "<hr />({$this->driver}): $sql<hr />";
		if (is_int($this->debug) && $this->debug>1) {
			echo '<pre>'; debug_print_backtrace(); echo '</pre>';
		}
		$error = $statement->errorInfo();
		if (!empty($error[2])) {
			echo "<div>{$error[2]}</div>";
		}
	}

	protected function getDriver() {
		$filename = dirname(__FILE__)."/drivers/driver.{$this->driver}.php";
		if (!file_exists($filename)) { return false; }
		include_once($filename);
		$classname = "Escher_PDOdriver_{$this->driver}";
		if (!class_exists($classname)) { return false; }
		return new $classname($this,$this->database);
	}
}
