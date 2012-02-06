<?php

class Helper_database_adodb extends Helper_database {
	protected $intTypes = array('tinyint','smallint','mediumint','int','bigint');
	protected $stringTypes = array('char','binary','varchar','varbinary');
	protected $contentTypes = array('tinytext','text','mediumtext','longtext');
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
				$ADODB_COUNTRECS = FALSE;
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
		$schema = array();
		$partitions = array(
			'fields'   => $this->prefix.$table,
			'metadata' => $this->prefix.$table.'_metadata',
			'content'  => $this->prefix.$table.'_content',
		);
		foreach($partitions as $n => $t) {
			$fields = array();
			$fraw = $this->adodb->MetaColumns($t);
			if ($fraw) {
				foreach($fraw as $f) {
					$fa = array(
						'type'           => $f->type,
						'length'         => $f->max_length,
						'not_null'       => $f->not_null,
						'auto_increment' => $f->auto_increment,
						'binary'         => $f->binary,
						'unsigned'       => $f->unsigned,
						'zerofill'       => $f->zerofill,
						'default'        => $f->has_default
							? $f->default_value
							: NULL,
					);
					
					foreach($fa as $k => $v) {
						if(empty($v)) { unset($fa[$k]); }
					}
					$fields[$f->name] = $fa;
				}
			}
			$schema[$n] = $fields;
		}
		return $schema;
	}

	function setSchema($table,$schema,$complete=FALSE) {
		// Get the current state of the database
		$dbSchema = $this->getSchema($table);

		// Detect new fields to add to DB schema
		$changeCols = array_diff_key($schema['fields'],$dbSchema['fields']);

		// Detect fields to modify in DB schema
		$checkCols = array_keys(
			array_intersect_key($schema['fields'],$dbSchema['fields']));
		foreach($checkCols as $c) {
			$after = array_diff($schema['fields'][$c],$dbSchema['fields'][$c]);
			if (empty($after)) { continue; }
			if ($complete) { $changeCols[$c] = $after; continue; }
			$before = array_intersect_key($dbSchema['fields'][$c],$after);
			$diff = array('type' => $dbSchema['fields'][$c]['type']);
			foreach($after as $k => $f) {
				switch ($k) {
					case 'type':
						if (in_array($after[$k],$this->intTypes)
							&& in_array($before[$k],$this->intTypes)
							&& array_search($after[$k],$this->intTypes) >
								array_search($before[$k],$this->intTypes)
						) {
							$diff[$k] = $f;
						} elseif (in_array($after[$k],$this->contentTypes)
							&& in_array($before[$k],$this->contentTypes)
							&& array_search($after[$k],$this->contentTypes) >
								array_search($before[$k],$this->contentTypes)
						) {
							$diff[$k] = $f;
						}
						break;
					case 'length':
						if ($f > $before['length']) {
							$diff[$k] = $f;
						}
						break;
					case 'unsigned': case 'auto_increment': break;
					default:
						$diff[$k] = $f; break;
				}
			}
			if (!empty($diff)) { $changeCols[$c] = $diff; }
		}

		// Start building ADOdb datadict syntax per table
		$changeTables = array(
			'main'   => array(),
			'metadata' => array(),
			'content'  => array(),
		);
		foreach($changeCols as $name => $c) {
			$def = "$name {$c['type']}";
			if (!empty($c['length'])) { $def .= "({$c['length']})"; }
			if (!empty($c['binary'])) { $def .= " BINARY"; }
			if (!empty($c['unsigned'])) { $def .= " UNSIGNED"; }
			if (!empty($c['zerofill'])) { $def .= " ZEROFILL"; }
			$def .= empty($c['not_null']) ? ' NULL' : ' NOTNULL';
			if (!empty($c['default'])) { $def .= " DEF {$this->q($c['default'])}"; }
			if (!empty($c['metadata'])) {
				$changeTables['metadata'][] = $def;
			} elseif (in_array($c['type'],$this->contentTypes)) {
				$changeTables['content'][] = $def;
			} else {
				$changeTables['main'][] = $def;
			}
		}

		$tableNames = array(
			'main'   => $this->prefix.$table,
			'metadata' => $this->prefix.$table.'_metadata',
			'content'  => $this->prefix.$table.'_content',
		);

		$this->connect();
		$this->db->debug = TRUE;
		$dict = NewDataDictionary($this->db);
		foreach($changeTables as $table => $fields) {
			if (!empty($fields)) {
				echo $this->db->CreateTableSQL(
					$tableNames[$table],
					implode(',',$fields)
				);
			}
		}
		die();
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
	
	function n($str) {
		$this->connect();
		$d = $this->adodb->nameQuote;
		return preg_replace(
			array('/[^\w_.*0-9]/','/([\w0-9_]+)/'),
			array('',$d.'$1'.$d),
			$str
		);
	}

	function q($str) {
		$this->connect();
		return $this->adodb->qstr($str);
	}

	function t($str) {
		return $this->n(parent::t($str));
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