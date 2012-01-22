<?php

/**
 * Helper_datasource_db.php
 * 
 * Datasource (DB) Helper class
 * @author Thom Stricklin <code@thomshouse.net>
 * @version 1.0
 * @package Escher
 */

/**
 * Datasource DB Helper class
 * Note: The Escher DB Datasource provides passive support for metadata/content tables.
 * @package Escher
 */
class Helper_datasource_db extends Helper_datasource {
	protected $db;
	function __construct($args=NULL) {
		if (is_null($args)) {
			$this->db = Load::DB();
		} else {
			$type = $args['type'];
			unset($args['type']);
			$this->db = Load::Helper('database',$type,$args);
		}
	}
	function set($model,$attrs=array(),$values=NULL,$options=array()) {
		unset($options['this']); extract($options);
		$db = $this->db;
		$meta_arr = array();
		$content_arr = array();
		if (is_object($model)) {
			// If model object is passed, get object schema and attributes
			$m = $model->_m();
			$id = @$model->id;
			$attrs = $model->_schema();
			if (!$attrs) {
				$attrs = $db->getSchema($m);
			}
			$values = array();
			foreach($attrs as $k => $v) {
				if (!isset($model->$k)) {
					unset($attrs[$k]);
				} elseif ($v=='meta') {
					$meta_arr[$k] = $model->$k;
					unset($attrs[$k]);
				} elseif ($v=='content') {
					$content_arr[$k] = $model->$k;
					unset($attrs[$k]);
				} elseif (in_array($v,array('time','datetime','timestamp'))) {
					$values[$k] = $this->time($model->$k);
				} elseif ($v=='date') {
					$values[$k] = $this->date($model->$k);
				} else {
					$values[$k] = $model->$k;
				}
			}
		} elseif (is_string($model)) {
			// If string is passed as $model, use $attrs and values from args
			$m = $model;
			// If no values given, check to see if $attrs is associative
			if (is_null($values) && array_keys($attrs)!=array_keys(array_values($attrs))) {
				$values = array_values($attrs);
				$attrs = array_keys($attrs);
			}
			$attrs = array_flip($attrs);
			$schema = $db->getSchema($model);
			foreach($attrs as $a => $t) {
				if(array_key_exists($a,$schema)) {
					if ($a=='id') {
						$id = $values[$t];	
					}
					$attrs[$a] = $schema[$a];
				} else {
					unset($attrs[$a]);
					unset($values[$t]);
				}
			}
		} else { return false; }

		// Build sql statement for attributes.
		$attr_sql = array();
		foreach($attrs as $a => $v) {
			$attr_sql[] = "$a = ?";
		}
		$attr_sql = implode(',',$attr_sql);

		// Check values to be scalar
		foreach($values as $k => $v) {
			if (!is_scalar($v)) {
				return false;
			}
		}

		// If an id is present, attempt to update
		if (isset($id)) {
			$result = $db->Execute("UPDATE ".$db->t($m)." SET ".$attr_sql." WHERE id=".$db->q($id),
				$values);
			// Check to make sure it was inserted (Select COUNT(*) is fallback for sql types w/out affectedRows())
			if (!($db->affectedRows() || $db->getOne("SELECT COUNT(*) FROM ".$db->t($m)." WHERE id=",array($id)))) {
				$result = false;
			}
		}
		// If no id is present or if row does not exist, insert
		if (!isset($id) || !$result) {
			$result = $db->Execute("INSERT INTO ".$db->t($m)." SET ".$attr_sql,$values);
			if ($result) {
				$id = $db->getAutoId();
				if (is_object($model)) {
					$model->id = $id;	
				}
			}
		}
		// If exec was successful, process metadata & content (Escher feature)
		if ($result) {
			if ((is_object($model) && $model->_metadata()) || !empty($metadata)) {
				$db->Execute("DELETE FROM ".$db->t($m.'_metadata')." WHERE id=?",array($id));
				foreach ($meta_arr as $n => $v) {
					if (!preg_match('/^[\d_]/',$n)) {
						$db->Execute("INSERT INTO ".$db->t($m.'_metadata')." SET id=?, name=?, value=?",array($id,$n,$v));
					}
				}
			}
			if ((is_object($model) && $model->_content()) || !empty($content)) {
				$db->Execute("DELETE FROM ".$db->t($m.'_content')." WHERE id=?",array($id));
				foreach ($content_arr as $n => $v) {
					if (!preg_match('/^[\d_]/',$n)) {
						if (is_scalar($v)) {
							$db->Execute("INSERT INTO ".$db->t($m.'_content')." SET id=?, name=?, value=?, serialized=0",array($id,$n,$v));
						} else {
							$db->Execute("INSERT INTO ".$db->t($m.'_content')." SET id=?, name=?, value=?, serialized=1",array($id,$n,serialize($v)));
						}
					}
				}
			}
			// Return id on success
			return $id;
		}
		// Return false on failure
		return false;
	}
	
	function get($model,$conditions=array(),$options=array()) {
		$select = '*'; $where = ''; $limit = 1; $order = ''; $group = '';
		unset($options['this']); extract($options);
		$db = $this->db;
		// Get the name of the model
		if (is_object($model)) {
			$m = $db->t($model->_m());
			// Special case for metadata lookups (i.e. oauth ids)
			$md = $model->_metadata();
			if ($select == '*' && $limit==1 && sizeof($conditions)==1 && is_scalar(current($conditions)) && ($md===TRUE || (is_array($md) && in_array(key($conditions),$md)))) {
				$m .= " m LEFT JOIN {$db->t($model->_m().'_metadata')} md ON m.id=md.id";
				if ($md===TRUE) {
					$conditions = array('OR','m.'.key($conditions) => current($conditions),array('md.name' => key($conditions),'md.value' => current($conditions)));
				} else {
					$conditions = array('md.name' => key($conditions),'md.value' => current($conditions));
				}
				$select = 'm.*';
			}
		} elseif (is_string($model)) {
			$m = $db->t($model);
		} elseif (is_array($model) && !empty($join) && sizeof($join)==sizeof($model)-1) {
			$m = '';
			foreach($model as $alias => $table) {
				if (!is_numeric($alias)) { $alias = " $alias"; }
				else { $alias = ''; }
				if (empty($m)) {
					$m = "{$db->t($table)}$alias";
				} else {
					$m .= " LEFT JOIN {$db->t($table)}$alias ON {$join[0]}";
					array_shift($join);
				}
			}
		} else { return false; }
		// If the conditions are provided as a string, assume an SQL query and pass through
		if (is_string($conditions)) {
			$where = "WHERE ".$conditions;
		// Otherwise, assemble the SQL statement from the array of conditions
		} elseif (is_array($conditions) && !empty($conditions)) {
			if (!$conditions = $this->traverseConditions($conditions)) {
				return false;
			}
			$where = "WHERE ".$conditions[0];
		}
		// Interpret array notation of order
		if (is_array($order)) {
			$o = array();
			foreach($order as $k => $v) {
				if ($v>0) { $o[] = "$k ASC"; }
				elseif ($v<0) { $o[] = "$k DESC"; }
				else { $o[] = $k; }
			}
			$order = implode(',',$o);
		}
		if (!empty($order)) {
			$order = "ORDER BY $order";
		}
		if (is_array($group)) {
			$group = implode(',',$group);
		}
		if (!empty($group)) {
			$group = "GROUP BY $group";
		}
		// If $limit is an array, accept it as skip,limit
		if (is_array($limit)) {
			$sqllimit = 'LIMIT '.(int)$limit[0].','.(int)$limit[1];
			$limit = (int)$limit[1];
		} elseif ($limit>0) {
			$sqllimit = "LIMIT ".(int)$limit;
		} else { $sqllimit = ''; }
		// If $fetch is provided, use fetcy type.  Otherwise, if our limit is one, get the row, else get all results
		if (!empty($fetch) && in_array($fetch,array('one','row','col','all','assoc'))) {
			$qtype = 'get'.ucfirst(strtolower($fetch));
		} elseif (!preg_match('/[,*]/',$select)) {
			$qtype = ($limit==1) ? 'getOne' : 'getCol';
		} else {
			$qtype = ($limit==1) ? 'getRow' : 'getAll';
		}
		// The DB Query
		$result = $db->$qtype("SELECT $select FROM $m $where $group $order $sqllimit",$conditions[1]);
		// If result is a single valid row and we are selecting everything, get metadata and content
		if (is_object($model) && $qtype=='getRow' && !empty($result['id'])) {
			$m = $db->t($model->_m());
			if ($meta = $db->getAssoc('SELECT name,value FROM '.$m.'_metadata WHERE id=?',array($result['id']))) {
				$result = array_merge($result,$meta);
				$result['_metadata'] = array_keys($meta);
			}
			if ($content = $db->getAssoc('SELECT name,value FROM '.$m.'_content WHERE id=?',array($result['id']))) {
				if ($serialized = $db->getCol('SELECT name FROM '.$m.'_content WHERE id=? && serialized=1',array($result['id']))) {
					foreach($serialized as $s) {
						$content[$s] = unserialize($content[$s]);
					}
				}
				$result = array_merge($result,$content);
				$result['_content'] = array_keys($content);
			}
			if (is_object($model)) {
				$model->assignVars($result);
			}
		}
		return $result;
	}
	
	function delete($model,$id=NULL) {
		if (is_object($model)) {
			$id = $model->id;
			$model = $model->_m();
		}
		if (is_null($id)) {
			return false;
		}
		$db = $this->db;
		if ($db->Execute('DELETE FROM '.$db->t($model).' WHERE id=?',array($id))) {
			$db->Execute('DELETE FROM '.$db->t($model.'_metadata').' WHERE id=?',array($id));
			$db->Execute('DELETE FROM '.$db->t($model.'_content').' WHERE id=?',array($id));
			return true;
		}
		return false;
	}
	
	function getSchema($model) {
		return $this->db->getSchema($model);
	}
	
	function traverseConditions($cond,$glue='AND') {
		$first = reset($cond);
		if (is_numeric(key($cond)) && $first=='NOT') {
			array_shift($cond);
			if ($result = $this->traverseConditions($cond)) {
				return array('NOT ('.$result[0].')',$result[1]);
			} else { return false; }
		} elseif (is_numeric(key($cond)) && in_array($first,array('AND','&&','OR','||','XOR'))) {
			array_shift($cond);
			if ($result = $this->traverseConditions($cond,$first)) {
				return array('('.$result[0].')',$result[1]);
			} else { return false; }
		}
		$pieces = array();
		$params = array();
		foreach($cond as $k => $v) {
			if(is_numeric($k)) {
				if (is_array($v)) {
					$result = $this->traverseConditions($v);
					$pieces[] = '('.$result[0].')';
					$params = array_merge($params,$result[1]);
				} else { return false; } // No other cases should have a numeric index
			} elseif ($k=='SQL') {
				$pieces[] = "($v)";
			} else {
				$result = $this->evalConditions($k,$v);
				if (!is_array($result)) { return false; }
				$pieces[] = $result[0];
				$params = array_merge($params,$result[1]);
			}
		}
		return array(implode(" $glue ",$pieces),$params);
	}
	
	function evalConditions($key,$value,$glue='AND') {
		if (strpos($key,'.')!==FALSE && strpos($key,'.')==strrpos($key,'.')) {
			$qkey = str_replace('.','.`',$key.'`');
		} else { $qkey = "`$key`"; }
		$db = $this->db;
		if (is_scalar($value)) {
			if (is_string($value) && in_array($value,array('IS NULL','IS NOT NULL'))) {
				return array("$qkey $value",array());
			} else {
				return array("$qkey = ?",array($value));
			}
		} elseif (is_array($value)) {
			if (sizeof($value)==0) { return false; }
			$first = reset($value);
			if (is_numeric(key($value)) && $first == 'NOT') {
				array_shift($value);
				$result = $this->evalConditions($key,$value);
				return array('NOT ('.$result[0].')',$result[1]);
			} elseif (is_numeric(key($value)) && in_array($first,array('AND','&&','OR','||','XOR'))) {
				array_shift($value);
				return $this->evalConditions($key,$value,$first);
			} elseif (sizeof(preg_grep('/\D/',array_keys($value)))==0) {
				if (sizeof($value)==0) { die_r(func_get_args()); }
				$in = array_fill(0,sizeof($value),'?');
				return array("$qkey IN(".implode(',',$in).")",$value);
			} else {
				$results = array();
				$params = array();
				foreach($value as $k => $v) {
					if (is_numeric($k) && in_array($v,array('IS NULL','IS NOT NULL'))) {
						$results[] = "$qkey $v";
					} elseif(is_numeric($k) && is_array($v)) {
						$result = $this->evalConditions($key,$v);
						$results = $result[0];
						$params = array_merge($params,$result[1]);
					} elseif (in_array($k,array('IN','NOT IN')) && is_array($v)) {
						$in = array_fill(0,sizeof($v),'?');
						$results[] = "$qkey $k(".implode(',',$in).')';
						$params = array_merge($params,$v);
					} elseif (in_array($k,array('BETWEEN','NOT BETWEEN')) && is_array($v) && sizeof($v)==2) {
						$v = array_values($v);
						$results[] = "($qkey $k ? AND ?)";
						$params[] = $v[0];
						$params[] = $v[1];
					} elseif(in_array($k,array('=','!=','<','<=','>','>=','<=>','<>','LIKE')) && is_scalar($v)) {
						$results[] = "$qkey $k ?";
						$params[] = $v;
					} elseif(in_array($k,array('IS','IS NOT')) && in_array($v,array('TRUE','FALSE','UNKNOWN',true,false))) {
						if (is_bool($v)) {
							$v = $v ? "TRUE" : "FALSE";
						}
						$results[] = "$qkey $k $v";	
					} elseif ($k=='date') {
						$results[] = '?';
						$params[] = $db->date($v);
					} elseif ($k=='time') {
						$results[] = '?';
						$params[] = $db->time($v);
					} elseif ($k=='SQL') {
						$results[] = "($v)";
					} else { return false; } // There are some more cases we can account for...  later.
				}
				return array('('.implode(" $glue ",$results).')',$params);
			}
		}
	}
	
	function date($ts) {
		return $this->db->date($ts);
	}
	function time($ts) {
		return $this->db->time($ts);
	}
}