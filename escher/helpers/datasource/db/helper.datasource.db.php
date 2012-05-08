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
	protected $intTypes = array(
		'tinyint'   => 1, // 256 (Range)
		'smallint'  => 2, // 65535
		'mediumint' => 3, // 16777215
		'int'       => 4, // 4294967295
		'bigint'    => 8, // 18446744073709551615
	);
	protected $stringTypes = array('char','binary','varchar','varbinary');
	protected $contentTypes = array(
		'tinytext'   => 1, // L < 2^8
		'text'       => 2, // L < 2^16
		'mediumtext' => 3, // L < 2^24
		'longtext'   => 4, // L < 2^32
	);

	function __construct($args=NULL) {
		if (is_null($args)) {
			$this->db = Load::DB();
		} else {
			$type = $args['type'];
			unset($args['type']);
			$this->db = Load::Helper('database',$type,$args);
		}
	}

	function set($model,$data=array(),$options=array()) {
		$db = $this->db;

		// If $model is an object, get type, values, and schema
		if (is_a($model,'Model')) {
			$m = $model->_m();
			$data = get_object_vars($model);
			$schema = $this->getSchema($model);
			// Update schema from model
			$this->setSchema($model);

		// If $model is string, get the object and schema
		} elseif (is_string($model)) {
			$m = $model;
			if ($model = Load::Model($m)) {
				$schema = $this->getSchema($model);
			} else {
				$schema = $this->getSchema($m);
			}

		// If $model is array, get the object and schema
		} elseif (is_array($model)) {
			$m = $model[1];
			if ($model = Load::Model($m)) {
				$schema = $this->getSchema($model);
			} else {
				$schema = $this->getSchema($m);
			}
		} else {
			return false;
		}

		// Clean and separate data
		$data = array_intersect_key($data,$schema['fields']);
		$metadata = array();
		$content = array();
		$primary = array();
		foreach($data as $k => $v) {
			// Clean up the data
			switch ($schema['fields'][$k]['type']) {
				// JSON-encode arrays
				case 'array':
					if (is_array($v)) { $data[$k] = json_encode($v); } break;
				// Ensure dates & times are properly formatted
				case 'time':
				case 'datetime':
				case 'timestamp':
					$data[$k] = $this->time($v); break;
				case 'date':
					$data[$k] = $this->date($v); break;
			}
			if (is_null($data[$k])) {
				unset($data[$k]);
				continue;
			}
			if (!is_scalar($data[$k])) { return false; }

			// Push primary key fields to $primary array
			if (in_array($k,(array)$schema['keys']['primary']['fields'])) {
				$primary[$k] = $data[$k];
				unset($data[$k]);

			// Push content and arrays into the content table
			} elseif (in_array(
				$schema['fields'][$k]['type'],
				array('array','content')
			)) {
				$content[$k] = $data[$k];
				unset($data[$k]);

			// Push metadata into the metadata array
			} elseif (!empty($schema['fields'][$k]['metadata'])) {
				$metadata[$k] = $data[$k];
				unset($data[$k]);
			}
		}

		$partitions = array(
			'data'     => $data,
			'metadata' => $metadata,
			'content'  => $content,
		);
		// Iterate through each of the partitions
		foreach($partitions as $partition => $values) {
			// Determine whether the primary key has been set
			$primarySet =
				sizeof($primary)==sizeof((array)$schema['keys']['primary']['fields']);

			// Skip content and metadata partition if nothing to save
			if (in_array($partition,array('metadata','content'))
				&& (!$primarySet || empty($values))
			) {
				continue;
			}

			// Set the table name of the current partition
			switch ($partition) {
				case 'data':     $partname = $db->t($m); break;
				case 'metadata': $partname = $db->t($m.'_metadata'); break;
				case 'content':  $partname = $db->t($m.'_content'); break;
			}

			// Build sql pieces for attributes and key
			$primary_sql = array();
			$attr_sql = array();
			foreach($primary as $a => $v) {
				$primary_sql[] = "$a = ?";
			}
			foreach($values as $a => $v) {
				$attr_sql[] = "$a = ?";
			}

			// If primary key is set, perform an update
			if ($primarySet) {
				$result = $db->Execute(
					"UPDATE " . $partname. " SET " . implode(',',$attr_sql)
						. " WHERE " . implode(' && ',$primary_sql),
					array_merge(array_values($values),array_values($primary))
				);
				// Check to make sure update occurred
				if (!($db->affectedRows() || $db->getOne(
						"SELECT COUNT(*) FROM " . $partname
							. " WHERE " . implode(' && ',$primary_sql),
						array_values($primary)
				))) {
					$result = false;
				}
			}

			// If primary key is not set or if row did not exist, insert
			if (!$primarySet || !$result) {
				if (!$primarySet) {
					if (sizeof((array)$schema['keys']['primary']['fields'])>1) {
						return false;
					}
					$key = reset($schema['keys']['primary']['fields']);
					if (empty($schema['fields'][$key]['auto_increment'])) {
						return false;
					}
				}
				$result = $db->Execute(
					"INSERT INTO " . $partname . " SET "
						. implode(',',array_merge($attr_sql,$primary_sql)),
					array_merge(array_values($values),array_values($primary))
				);
				if (!$result) { return false; }
				if (!$primarySet) {
					$id = $db->getAutoId();
					$primary = array($key => $id);
					if (is_object($model)) {
						$model->$key = $id;	
					}
				}
			}
		}

		// Return id or success
		return isset($id) ? $id : TRUE;
	}
	
	function get($models,$conditions=array(),$options=array()) {
		// Clean up the options
		$options = array_merge(
			array(
				'select' => '*',
				'limit'  => 1,
				'order'  => '',
				'group'  => '',
				'joins'   => array(),
			),
			$options
		);
		if (!is_array($options['select'])) {
			$options['select'] = explode(',',$options['select']);
		}

		$db = $this->db;
		
		// Convert a single model to an array
		if (is_a($models,'Model') || is_string($models)
			|| (is_array($models) && sizeof($models)==2 && empty($options['join']))) {
			$models = array($models);
		} elseif (!is_array($models)) {
			return false;
		}

		$tables = array();
		$joins = array();
		$toDecode = array();
		$j = 0; // Explicit join iterator

		// Iterate through the models array
		foreach($models as $alias => $m) {
			// Get model name and object
			if (is_a($m,'Model')) {
				$model = $m;
				$m = $model->_m();
			} elseif (is_array($m)) {
				$model = Load::Model($m);
				$m = $model->_m();
			} elseif (is_string($m)) {
				$model = Load::Model($m);
			} else { return false; }

			// Set the table alias if it's not a string
			if (is_numeric($alias)) { $alias = $m; }

			// Load schema
			if (is_a($model,'Model')) {
				$schema = array(
					'fields' => $model->_schemaFields,
					'keys' => $model->_schemaKeys,
				);
			} elseif (!$schema = $this->getSchema($m)) {
				return false;
			}

			// Get which partitions we are checking
			$partitions = array();
			foreach($schema['fields'] as $n => $f) {
				// See if the current field is in the select
				if ($fields = preg_grep("/^({$alias}\.)?(\*|$n)$/",$options['select'])) {
					if (in_array($f['type'],array('content','array'))) {
						if($f['type']=='array') {
							$toDecode[] = $n;
						}
						$partitions['content'] = TRUE;
					} elseif (!empty($f['metadata'])) {
						$partitions['metadata'] = TRUE;
					} else {
						$partitions['data'] = TRUE;
					}
				}
			}
			$partnames = array();
			$partnames['data'] = $db->t($m).' '.$db->n($alias);
			$partnames['metadata'] = $db->t($m.'_metadata').' '.$db->n($alias.'_m');
			$partnames['content'] = $db->t($m.'_content').' '.$db->n($alias.'_c');

			// Set our joins
			for($i=1;$i<sizeof($partitions);$i++) {
				// Implicit partition joins
				$joins[] = implode(',',(array)$schema['keys']['primary']['fields']);
			}
			// Explicit joins from options
			if ($j) {
				if (sizeof($options['joins'])<$j) { return false; }
				$joins[] = $options['joins'][$j-1];
			}
			$j++;

			// Add all partition tables to the tables array
			$tables = array_merge(
				$tables,
				array_values(array_intersect_key($partnames,$partitions))
			);
		}
		if (empty($tables)) { return FALSE; }
		
		// Get the SQL from (tables/joins) clause
		$from = '';
		foreach($tables as $k => $t) {
			if ($k==0) {
				$from = $t;
			} else {
				$j = $joins[$k-1];
				$from .= " LEFT JOIN $t";
				$from .= strpos($k,'=')!==FALSE
					? " ON $j"
					: " USING($j)";
			}
		}

		// Get SQL where clause
		if (is_string($conditions)) {
			// If conditions are a string, assume an SQL query and pass through
			$where = "WHERE ".$conditions;
			$conditions = array($conditions,array());
		// Otherwise, assemble the SQL statement from the array of conditions
		} elseif (is_array($conditions) && !empty($conditions)) {
			if (!$conditions = $this->traverseConditions($conditions)) {
				return false;
			}
			$where = "WHERE ".$conditions[0];
		} else {
			$where = '';
			$conditions = array('',array());
		}

		// Get SQL order clause
		if (is_array($options['order'])) {
			// Interpret array notation
			$o = array();
			foreach($options['order'] as $k => $v) {
				if ($v>0) { $o[] = "$k ASC"; }
				elseif ($v<0) { $o[] = "$k DESC"; }
				else { $o[] = $k; }
			}
			$options['order'] = implode(',',$o);
		}
		$order = !empty($options['order']) ? "ORDER BY {$options['order']}" : '';

		// Get SQL group clause
		if (is_array($options['group'])) {
			$options['group'] = implode(',',$options['group']);
		}
		$group = !empty($options['group']) ? "GROUP BY {$options['group']}" : '';

		// Get SQL limit clause
		if (is_array($options['limit'])) {
			// Convert array to skip,limit
			$limit = 'LIMIT '.(int)$options['limit'][0].','.(int)$options['limit'][1];
			$options['limit'] = (int)$options['limit'][1];
		} elseif ($options['limit']>0) {
			$limit = "LIMIT ".(int)$options['limit'];
		} else { $limit = ''; }

		// Get SQL select clause
		$select = implode(',',$db->n($options['select']));

		// If $fetch is provided, use fetch type.  Otherwise, if our limit is one, get the row, else get all results
		if (!empty($options['fetch']) && in_array($options['fetch'],array('one','row','col','all','assoc'))) {
			$qtype = 'get'.ucfirst(strtolower($options['fetch']));
		} elseif (sizeof($options['select'])==1 && !preg_match('/[*]/',$options['select'][0])) {
			$qtype = ($options['limit']==1) ? 'getOne' : 'getCol';
		} else {
			$qtype = ($options['limit']==1) ? 'getRow' : 'getAll';
		}

		// The DB Query
		$result = $db->$qtype("SELECT ".$select." FROM $from $where $group $order $limit",$conditions[1]);

		// Logic for decoding array fields
		if (!empty($result) && !empty($toDecode)) {
			if ($qtype=='getOne') {
				if (in_array(reset($options['select']),$toDecode)) {
					$result = json_decode($result,TRUE);
				}
			} elseif ($qtype=='getCol') {
				if (in_array(reset($options['select']),$toDecode)) {
					foreach($result as $k => $r) {
						$result[$k] = json_decode($r,TRUE);
					}
				}
			} elseif ($qtype=='getRow') {
				foreach($toDecode as $decode) {
					if (array_key_exists($decode,$result)) {
						$result[$decode] = json_decode($result[$decode],TRUE);
					}
				}
			} elseif ($qtype=='getAll') {
				foreach($result as $k => $r) {
					foreach($toDecode as $decode) {
						if (array_key_exists($decode,$r)) {
							$result[$k][$decode] = json_decode($r[$decode],TRUE);
						}
					}
				}
			}
		}

		// If result is a single valid row and we are selecting everything, get metadata and content
		if ($result && sizeof($models)==1 && is_a(reset($models),'Model') && $qtype=='getRow') {
			$model = reset($models);
			$model->assignVars($result);
		}
		return $result;
	}
	
	function delete($model,$key=NULL) {
		// Get the key, name, and object
		if (is_a($model,'Model')) {
			$key = get_object_vars($model);
			$m = $model->_m();
		} elseif (is_array($model)) {
			$m = $model[1];
			$model = Load::Model($m);
		} elseif (is_string($model)) {
			$m = $model;
			$model = Load::Model($m);
		} else { return false; }

		// Get the schema
		if (is_a($model,'Model')) {
			$schemaKeys = $model->_schemaKeys;
		} else {
			$schema = $this->_getSchema($m);
			$schemaKeys = $schema['keys'];
		}

		// Clean the key
		if (is_scalar($key)) {
			if (sizeof($schemaKeys['primary']['fields'])!=1) { return false; }
			$key = array(reset($schemaKeys['primary']['fields']) => $key);
		} else {
			$key = array_intersect_key(
				(array)$key,
				array_flip($model->_schemaKeys['primary']['fields'])
			);
		}

		// Key must be full-sized
		if (sizeof($key)!=sizeof($schemaKeys['primary']['fields'])) {
			return false;
		}

		// Get SQL where clause from key
		if (!$where = $this->traverseConditions($key)) {
			return false;
		}

		// Run the delete
		$db = $this->db;
		if ($db->Execute('DELETE FROM '.$db->t($m).' WHERE '.$where[0],$where[1])) {
			$db->Execute('DELETE FROM '.$db->t($m.'_metadata').' WHERE '.$where[0],$where[1]);
			$db->Execute('DELETE FROM '.$db->t($m.'_content').' WHERE '.$where[0],$where[1]);
			return true;
		}
		return false;
	}
	
	function getSchema($model) {
		// If $model is a string or array, load object
		if (is_string($model)) {
			$m = $model;
			$model = Load::Model($model);
		} elseif (is_array($model)) {
			$m = $model[1];
			$model = Load::Model($model);
		}

		// Return model schema if available
		if (is_a($model,'Model')) {
			return array(
				'fields' => $model->_schemaFields,
				'keys' => $model->_schemaKeys,
			);
		}

		$dbSchema = $this->db->getSchema($m);
		$fields = array();
		foreach($dbSchema['fields'] as $name => $r) {
			$fa = $r;
			if (array_key_exists($r['type'],$this->intTypes)) {
				$fa['type'] = 'int';
				$fa['range'] = pow(2,8*$this->intTypes[$r['type']])-1;
				unset($fa['length']);
			} elseif (in_array($r['type'],$this->stringTypes)) {
				$fa['type'] = 'string';
			} elseif (array_key_exists($r['type'],$this->contentTypes)) {
				$fa['type'] = 'content';
				$fa['length'] = pow(2,8*$this->contentTypes[$r['type']])-1;
			}
			$fields[$name] = $fa;
		}
		return array(
			'fields' => $fields,
		);
	}

	function setSchema($model) {
		// Get the model as an object
		if (is_string($model) || is_array($model)) {
			$model = Load::Model($model);
		}
		if (!is_a($model,'Model')) { return false; }

		$fields = $model->_schemaFields;
		foreach($fields as $name => $field) {
			switch ($field['type']) {
				case 'int':
					foreach($this->intTypes as $type => $bytes) {
						if ($field['range']<=pow(2,8*$bytes)) {
							$field['type'] = $type;
							break;
						}
					}
					$field['length'] = max(1,ceil(log10($field['range'])));
					unset($field['range']);
					break;
				case 'string': $field['type'] = 'varchar'; break;
				case 'content': case 'array':
					foreach($this->contentTypes as $type => $bytes) {
						if ($field['length']<=pow(2,8*$bytes)) {
							$field['type'] = $type;
							break;
						}
					}
					break;
			}
			$fields[$name] = $field;
		}
		return $this->db->setSchema($model->_m(),array(
			'fields' => $fields,
			'keys'   => $model->_schemaKeys,
		));
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
			if (is_numeric(key($value)) && is_string($first) && $first == 'NOT') {
				array_shift($value);
				$result = $this->evalConditions($key,$value);
				return array('NOT ('.$result[0].')',$result[1]);
			} elseif (is_numeric(key($value)) && is_string($first)
				&& in_array($first,array('AND','&&','OR','||','XOR'))
			) {
				array_shift($value);
				return $this->evalConditions($key,$value,$first);
			} elseif (sizeof(preg_grep('/\D/',array_keys($value)))==0) {
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