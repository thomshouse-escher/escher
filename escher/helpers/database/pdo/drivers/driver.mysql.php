<?php

class Escher_PDOdriver_mysql extends EscherObject {
	protected $intTypes = array('tinyint','smallint','mediumint','int','bigint');
	protected $stringTypes = array('char','binary','varchar','varbinary');
	protected $contentTypes = array('tinytext','text','mediumtext','longtext');
	protected $pdo;

	function __construct($pdo) {
		$this->pdo = $pdo;
	}

	function getSchema($table) {
		// Prefix the table
		$table = $this->pdo->t($table,FALSE);

		// Get the column info from information_schema
		$result = $this->pdo->getAll(
			'SELECT * FROM ' . $this->pdo->n('information_schema.COLUMNS')
			. ' WHERE '.$this->pdo->n('TABLE_NAME').' IN(?,?,?) ORDER BY '
			. $this->pdo->n('TABLE_NAME'). '=? DESC,'
			. $this->pdo->n('TABLE_NAME'). '=? DESC,'
			. $this->pdo->n('ORDINAL_POSITION') .' ASC', array(
				$table,
				$table.'_metadata',
				$table.'_content',
				$table,
				$table.'_metadata',
			));

		// Build the fields array
		$fields = array();
		$tables = array();
		foreach($result as $r) {
			$tables[] = $r['TABLE_NAME'];
			if(array_key_exists($r['COLUMN_NAME'],$fields)) { continue; }
			$field = array(
				'type'           => $r['DATA_TYPE'],
				'length'         => $r['CHARACTER_MAXIMUM_LENGTH'],
				'null'           => $r['IS_NULLABLE']=='YES',
				'auto_increment' => strpos($r['EXTRA'],'auto_increment')!==FALSE,
				'binary'         => preg_match('/_bin$/i',$r['COLLATION_NAME']),
				'unsigned'       => strpos($r['COLUMN_TYPE'],'unsigned')!==FALSE,
				'zerofill'       => strpos($r['COLUMN_TYPE'],'zerofill')!==FALSE,
				'default'        => $r['COLUMN_DEFAULT']
			);
			if (in_array($field['type'],$this->intTypes)) {
				$field['length'] = $r['NUMERIC_PRECISION'];
			}
			if ($r['TABLE_NAME']==$table.'_metadata') {
				$field['metadata'] = TRUE;
			}
			foreach($field as $k => $v) {
				if(empty($v)) { unset($field[$k]); }
			}
			$fields[$r['COLUMN_NAME']] = $field;
		}

		// Get the key info from information_schema
		$keys = array();
		$tables = array_intersect(
			array($table,$table.'_metadata',$table.'_content'),
			$tables
		);
		foreach($tables as $t) {
			$kresult = $this->pdo->getAll("SHOW KEYS FROM `$t`");
			foreach($kresult as $r) {
				$kname = $r['Key_name'];
				if ($kname=='PRIMARY') {
					if ($t!=$table) { continue; }
					$kname = 'primary';
				}
				if(!array_key_exists($kname,$keys)) {
					$keys[$kname] = array('fields' => array());
				}
				if (!in_array($r['Column_name'],$keys[$kname]['fields'])) {
					$keys[$kname]['fields'][] = $r['Column_name'];
				}
				if ($kname=='primary') {
					$keys[$kname]['type'] = 'primary';
				} else {
					$keys[$kname]['type'] = $r['Non_unique']
						? 'index'
						: 'unique';
				}
			}
		}

		// Return fields and keys
		return array(
			'fields' => $fields,
			'keys'   => $keys,
		);
	}

	function setSchema($table,$schema,$dbSchema,$complete=FALSE) {
		// Detect new fields to add to DB schema
		$createFields = array_diff_key($schema['fields'],$dbSchema['fields']);

		// Detect fields to drop from DB schema
		if ($complete) {
			$dropFields = array_diff_key($dbSchema['fields'],$schema['fields']);
		} else {
			$dropFields = array();
		}

		// Detect fields to modify in DB schema
		$alterFields = array();
		$checkFields = array_keys(
			array_intersect_key($schema['fields'],$dbSchema['fields']));
		foreach($checkFields as $c) {
			// Grab the new version of the field
			$changeTo = $schema['fields'][$c];
			// Preserve old metadata status (can't move between partitions)
			if (isset($dbSchema['fields'][$c]['metadata'])) {
				$changeTo['metadata'] = $dbSchema['fields'][$c]['metadata'];
			} else {
				unset($changeTo['metadata']);
			}

			// Compare new vs. old fields
			$after = array_diff($changeTo,$dbSchema['fields'][$c]);
			// If there's no change whatsoever, skip this field
			if (empty($after)) { continue; }
			// If we're doing a complete setSchema, skip comparisons
			if ($complete) { $alterFields[$c] = $changeTo; continue; }

			// Otherwise iterate over properties to ensure non-destructive changes
			$before = array_intersect_key($dbSchema['fields'][$c],$after);
			$diff = FALSE;
			foreach($after as $k => $f) {
				switch ($k) {
					// If type has changed, choose the larger type
					case 'type':
						if (in_array($after[$k],$this->intTypes)
							&& in_array($before[$k],$this->intTypes)
						) {
							if(array_search($after[$k],$this->intTypes) >
								array_search($before[$k],$this->intTypes)
							) {
								$diff = TRUE;
							} else {
								$changeTo[$k] = $before[$k];
							}
						} elseif (in_array($after[$k],$this->contentTypes)
							&& in_array($before[$k],$this->contentTypes)
						) {
							if (array_search($after[$k],$this->contentTypes) >
								array_search($before[$k],$this->contentTypes)
							) {
								$diff = TRUE;
							} else {
								$changeTo[$k] = $before[$k];
							}
						}
						break;
					// If length has changed, choose the greater length
					case 'length':
						if ($f > $before['length']) {
							$diff = TRUE;
						} else {
							$changeTo[$k] = $before[$k];
						}
						break;
					// Ignore everything else
					default: break;
				}
			}
			if ($diff) { $alterFields[$c] = $changeTo; }
		}

		// Detect new keys to add to DB schema
		$createKeys = array_diff_key($schema['keys'],$dbSchema['keys']);

		// Detect keys to drop from DB schema
		if ($complete) {
			$dropKeys = array_diff_key($dbSchema['keys'],$schema['keys']);
		} else {
			$dropKeys = array();
		}

		// Detect keys to modify in DB schema
		$alterKeys = array();
		$checkKeys = array_keys(
			array_intersect_key($schema['keys'],$dbSchema['keys']));
		foreach($checkKeys as $c) {
			// We can't change the primary key
			if ($c=='primary') { continue; }
			if($complete || sizeof(array_diff(
					$schema['keys'][$c]['fields'],
					$dbSchema['keys'][$c]['fields']
				)) > 0 || sizeof(array_diff(
					$dbSchema['keys'][$c]['fields'],
					$schema['keys'][$c]['fields']
				)) > 0
			) {
				$alterKeys[$c] = $schema['keys'][$c];
			}
		}
		
		// If we're not making any changes, just return
		if (empty($createFields) && empty($alterFields) && empty($dropFields)
			&& empty($createKeys) && empty($alterKeys) && empty($dropKeys)
		) {
			return array();
		}

		// Save the name of the tables.  We'll reuse these.
		$fieldsTable = $this->pdo->t($table,FALSE);
		$metadataTable = $this->pdo->t($table.'_metadata',FALSE);
		$contentTable = $this->pdo->t($table.'_content',FALSE);

		// Split up the old schema by partition to determine create vs. alter
		$oldParts = $newParts = $newKeyParts = array(
			$fieldsTable => array(),
			$metadataTable => array(),
			$contentTable => array(),
		);
		foreach($dbSchema['fields'] as $name => $f) {
			if (!empty($f['metadata'])) {
				$oldParts[$metadataTable][] = $name;
			} elseif (in_array($f['type'],$this->contentTypes)) {
				$oldParts[$contentTable][] = $name;
			} else {
				$oldParts[$fieldsTable][] = $name;
			}
		}

		// Split up the current schema by partition for iteration
		foreach($schema['fields'] as $name => $f) {
			if (!empty($f['metadata'])) {
				$newParts[$metadataTable][] = $name;
			} elseif (in_array($f['type'],$this->contentTypes)) {
				$newParts[$contentTable][] = $name;
			} else {
				$newParts[$fieldsTable][] = $name;
			}
		}

		// Protect and/or distribute the primary key
		$primaryFields = array();
		if (array_key_exists('primary',$dbSchema['keys'])) {
			foreach ($dbSchema['keys']['primary']['fields'] as $f) {
				$primaryFields[$f] = $dbSchema['fields'][$f];
				unset($alterFields[$f]);
				unset($dropFields[$f]);
			}
		} elseif (array_key_exists('primary',$schema['keys'])) {
			foreach ($schema['keys']['primary']['fields'] as $f) {
				$primaryFields[$f] = $schema['fields'][$f];
			}
		}

		// Split up the keys by partition
		foreach($schema['keys'] as $name => $k) {
			foreach($newKeyParts as $part => $f) {
				if(sizeof(array_intersect($k['fields'],$newParts[$part]))
					==sizeof($k['fields'])
				) {
					$newKeyParts[$part][] = $name;
				}
			}
		}
		
		// Start changing the DB structure
		$allSql = '';
		foreach($newParts as $part => $fields) {
			if (empty($fields)) { continue; }
			// If partition previously empty, we must create the table
			if (empty($oldParts[$part])) {
				$sql = "CREATE TABLE IF NOT EXISTS {$this->pdo->n($part)} (";
				$fieldsSql = array();
				foreach($primaryFields as $name => $f) {
					// Only the primary partition should have an auto-increment
					if ($part!=$fieldsTable) {
						unset($f['auto_increment']);
					}
					$fs = $this->pdo->n($name).' '.$this->_fieldSQL($f);
					$fieldsSql[] = $fs;
				}
				foreach($createFields as $name => $f) {
					if (in_array($name,$fields)
						&& !array_key_exists($name,$primaryFields)
					) {
						$fs = $this->pdo->n($name).' '.$this->_fieldSQL($f);
						$fieldsSql[] = $fs;
					}
				}
				if (!empty($primaryFields)) {
					$fs = $this->_keySQL('primary',array(
						'type'   => 'primary',
						'fields' => array_keys($primaryFields),
					));
					$fieldsSql[] = $fs;
				}
				foreach($createKeys as $name => $f) {
					if ($name!='primary' && in_array($name,$newKeyParts[$part])) {
						$fs = $this->_keySQL($name,$f);
						$fieldsSql[] = $fs;
					}
				}
				if (!empty($fieldsSql)) {
					$allSql .= $sql.implode(', ',$fieldsSql)
						.') ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; ';
				}
			} else {
				$sql = "ALTER TABLE {$this->pdo->n($part)} ";
				$fieldsSql = array();
				foreach($createFields as $name => $f) {
					if (in_array($name,$fields)) {
						if (!empty($f['renames'])) {
							$rename = array_intersect((array)$f['renames'],$oldParts[$part]);
						}
						if (!empty($f['renames']) && sizeof($rename)==1)
						{
							$fs = 'CHANGE COLUMN '.$this->pdo->n($rename[0]).' '
								.$this->pdo->n($name).' '.$this->_fieldSQL($f);
						} else {
							$fs = 'ADD COLUMN '.$this->pdo->n($name).' '
								.$this->_fieldSQL($f);
						}
						$fieldsSql[] = $fs;
					}
				}
				foreach($alterFields as $name => $f) {
					if (in_array($name,$fields)) {
						$fs = 'CHANGE COLUMN '.$this->pdo->n($name).' '
							.$this->pdo->n($name).' '.$this->_fieldSQL($f);
						$fieldsSql[] = $fs;
					}
				}
				foreach($dropFields as $name => $f) {
					if (in_array($name,$fields)) {
						$fs = 'DROP COLUMN '.$this->pdo->n($name).' '
							.$this->pdo->n($name).' '.$this->_fieldSQL($f);
						$fieldsSql[] = $fs;
					}
				}
				foreach($createKeys as $name => $f) {
					if (in_array($name,$newKeyParts[$part])) {
						if (!empty($f['renames'])) {
							$rename = array_intersect(
								(array)$f['renames'],
								array_keys($dbSchema['keys'])
							);
						}
						if (!empty($f['renames']) && sizeof($rename)==1) {
							$fieldsSql[] = $this->_dropKeySQL($rename[0],$f);
						}
						$fieldsSql[] = 'ADD '.$this->_keySQL($name,$f);
					}
				}
				foreach($alterKeys as $name => $f) {
					if (in_array($name,$newKeyParts[$part])) {
						$fieldsSql[] = $this->_dropKeySQL($name,$f);
						$fieldsSql[] = 'ADD '.$this->_keySQL($name,$f);
					}
				}
				foreach($dropKeys as $name => $f) {
					if (in_array($name,$newKeyParts[$part])) {
						$fieldsSql[] = $this->_dropKeySQL($name,$f);
					}
				}
				if (!empty($fieldsSql)) {
					$allSql .= $sql.implode(', ',$fieldsSql).'; ';
				}
			}
		}
		$this->pdo->execute($allSql);
		return array(
			'fields' => array_merge(
				$createFields,
				$alterFields,
				array_fill_keys(array_keys($dropFields),NULL)
			),
			'keys' => array_merge(
				$createKeys,
				$alterKeys,
				array_fill_keys(array_keys($dropKeys),NULL)
			),
		);
	}

	protected function _fieldSQL($field) {
		$sql = $field['type'];
		if ((in_array($field['type'],$this->intTypes)
				|| in_array($field['type'],$this->stringTypes))
			&& !empty($field['length'])
		) {
			$sql .= "({$field['length']})";
		}
		if (!empty($field['unsigned'])) { $sql .= " UNSIGNED"; }
		if (!empty($field['zerofill'])) { $sql .= " ZEROFILL"; }
		if (in_array($field['type'],$this->contentTypes)
			&& !empty($field['binary'])
		) {
			$sql .= " BINARY";
		}
		$sql .= !empty($field['null']) ? ' NULL' : ' NOT NULL';
		if (isset($field['default'])) {
			$sql .= " DEFAULT {$this->pdo->q($field['default'])}";
		}
		if (!empty($field['auto_increment'])) {
			$sql .= " AUTO_INCREMENT";
		}

		return $sql;
	}

	protected function _keySQL($name,$key) {
		switch ($key['type']) {
			case 'primary': $sql = 'PRIMARY KEY'; break;
			case 'unique': $sql = 'UNIQUE '.$this->pdo->n($name); break;
			case 'key': $sql = 'INDEX '.$this->pdo->n($name); break;
		}
		foreach($key['fields'] as $k => $f) {
			$key['fields'][$k] = $this->pdo->n($f);
		}
		$sql .= ' ('.implode(',',$key['fields']).')';
		return $sql;
	}

	protected function _dropKeySQL($name,$key) {
		switch ($key['type']) {
			case 'primary': $sql = 'DROP PRIMARY KEY'; break;
			default: $sql = 'DROP INDEX '.$this->pdo->n($name); break;
		}
		return $sql;
	}
}