<?php

abstract class EscherModel extends EscherObject {
	protected $_schemaFields = array();
	protected $_schemaKeys = array();
	protected $_schemaTriggers = array();
	protected $_outputType = 'php';

	protected static $_parsedNew = array();

	public function __construct($key=NULL) {
		parent::__construct();
		// Expand schema shorthands and defaults
		$this->_expandSchema();

		// If a key was provided, let's load the model
		if(!is_null($key)) {
			$this->load($key);
		}
	}
	
	public function load($key=NULL) {
		// If this object is already loaded, just reload (ignore provided keys)
		if (!empty($this->id)) {
			$key = array('id' => $this->id);
		// If only the primary id was provided as key, format for datasource
		} elseif (is_scalar($key)) {
			$key = array('id' => $key);
		// And if our key is invalid, return false
		} else if (!is_array($key) || !empty($key)) {
			return false;
		}
		// If we can't load from cache, load from datasources and cache it
		if (!$this->loadCached($key)) {
			if(!$this->loadUncached($key)) {
				return false;
			} else {
				$this->cache();
			}
		}
		return true;
	}
	
	function loadCached($keys) {
		$sources = $this->_getCacheDatasources();
		// Iterate through our datasources, trying to load the model
		foreach($sources as $s) {
			$ds = Load::Datasource($s);
			if ($result = $ds->get($this,$keys)) {
				// Assign class vars (metadata, content, etc.) and return
				//$this->assignClassVars($result);
				return true;
			}
		}
		return false;
	}

	function loadUncached($keys) {
		$sources = $this->_getDatasources();
		// Iterate through our datasources, trying to load the model
		foreach($sources as $s) {
			$ds = Load::Datasource($s);
			if ($result = $ds->get($this,$keys)) {
				// When we find the datasource, set it
				$this->_datasource = $s;
				// Assign class vars (metadata, content, etc.)
				//$this->assignClassVars($result);
				return true;
			}
		}
		return false;
	}
	
	public function save() {
		// Touch the model if this is the first save
		if (empty($this->id) && empty($this->ctime)) {
			$this->touch();
		}
		$sources = $this->_getDatasources();
		// Iterate through the datasources and save
		// Note: Only new objects should have to iterate
		foreach($sources as $s) {
			$ds = Load::Datasource($s);
			if ($ds->set($this)) {
				if (!isset($this->_datasource)) {
					$this->_datasource = $s;
				}
				$this->cache();
				return true;
			}
		}
		return false;
	}
	
	// Cache the model, but don't do a full save
	public function cache() {
		$sources = $this->_getCacheDatasources();
		foreach($sources as $s) {
			$ds = Load::Datasource($s);
			$ds->set($this);
		}
		return true;
	}

	// Delete the current object from the datasource and cache
	public function delete() {
		$this->expire();
		if (!isset($this->_datasource)) {
			return false;
		}
		$ds = Load::Datasource($this->_datasource);
		if ($ds->delete($this)) {
			return true;
		}
		return false;
	}
	
	// Delete the current object from cache
	function expire() {
		$sources = $this->_getCacheDatasources();
		foreach($sources as $s) {
			$ds = Load::Datasource($s);
			$ds->delete($this);
		}
		return true;
	}
	
	function touch($fields=NULL,$model=NULL) {
		// If no fields provided, try the triggers
		if (empty($fields)) {
			$fields = array();
			if (!empty($this->_schemaTriggers['touch_create'])) {
				$fields = array_merge($fields,
					$this->_schemaTriggers['touch_create']
				);
			}
			if (!empty($this->_schemaTriggers['touch_modify'])) {
				$fields = array_merge($fields,
					$this->_schemaTriggers['touch_modify']
				);
			}
		}
		// If no model provided, assume the current user
		if (is_null($model)) { $model = Load::USER(); }
		// Extract the model type & id
		if (is_a($model,'Model') && !empty($model->id)) {
			$mtype = $model->_m();
			$mid = $model->id;
		} else {
			$mtype = 0;
			$mid = 0;
		}

		// Iterate through the fields and set the valid ones
		foreach($fields as $f) {
			if(!array_key_exists($f,$this->_schemaFields)
				|| (in_array($f,$this->_schemaTriggers['touch_create'])
					&& !empty($this->$f))
			) { continue; }
			switch ($this->_schemaFields[$f]['type']) {
				case 'datetime': $this->$f = NOW; break;
				case 'string': $this->$f = $mtype; break;
				case 'int': $this->$f = $mid; break;
			}
		}
	}

	// Iterates through form input and assigns variables
	function parseInput($uniqid=NULL) {
		$input = Load::Input();
		if (!empty($this->id)) {
			if (!empty($input->post['model'][$this->_m()][$this->id])) {
				$data = $input->post['model'][$this->_m()][$this->id];
			} else {
				return false;
			}
		} elseif (!is_null($uniqid)
			&& !empty($input->post['model'][$this->_m()]['new'][$uniqid])
		) {
			$data = $input->post['model'][$this->_m()]['new'][$uniqid];
		} elseif (!empty($input->post['model'][$this->_m()]['new'])) {
			$uniqid = reset(array_diff(
				array_keys($input->post['model'][$this->_m()]['new']),
				static::$_parsedNew
			));
			static::$_parsedNew[] = $uniqid;
			$data = $input->post['model'][$this->_m()]['new'][$uniqid];
		} else {
			return false;
		}
		$this->assignVars($data);
		return true;
	}

	// Get the datasources for this object
	protected function _getDatasources() {
		if (isset($this->_datasource)) {
			$sources = array($this->_datasource);
		} else {
			$CFG = Load::Config();
			if (isset($CFG['datasource_order'][$this->_m()])) {
				$sources = $CFG['datasource_order'][$this->_m()];
			} else {
				$sources = $CFG['datasource_order']['all'];
			}
		}
		return $sources;
	}

	// Get the cache datasources for this object
	protected function _getCacheDatasources() {
		$CFG = Load::Config();
		if (isset($CFG['datasource_cache_order'][$this->_m()])) {
			$sources = $CFG['datasource_cache_order'][$this->_m()];
		} elseif (isset($CFG['datasource_cache_order']['all'])) {
			$sources = $CFG['datasource_cache_order']['all'];
		} else {
			$sources = array('arrcache');
		}
		if (!in_array('arrcache',$sources)) {
			array_unshift($sources,'arrcache');
		}
		return $sources;
	}

	// Display a view for this model, using provided data or object data
	final function display($view,$data=NULL,$type=NULL) {
		if (is_null($type)) { $type = $this->_outputType; }
		$out = Load::Output($type,$this);
		$out->assignVars($data);
		if (is_null($data)) {
			$nameFormat = !empty($this->id)
				? "model[{$this->_m()}][{$this->id}][%s]"
				: "model[{$this->_m()}][new][".uniqid()."][%s]";
			$out->assignModelVars($this,$nameFormat);
		}
		return $out->displayModelView($this,$view);
	}

	function find($conditions=array(),$options=array()) {
		if(empty($options['limit'])) {
			$options['limit'] = 0;
		}
		$sources = $this->_getDatasources();
		// Iterate through our datasources, waiting for results
		foreach($sources as $s) {
			$ds = Load::Datasource($s);
			if ($result = $ds->get($this->_m(),$conditions,$options)) {
				return $result;
			}
		}
		return false;
	}

	// Override assignVars to protect class variables
	public function assignVars($vars) {
		if (!is_array($vars) || array_values($vars)==$vars) {
			return false;
		}
		$classvars = array_keys(get_class_vars(get_class($this)));
		foreach($classvars as $key) {
			if (strpos($key,'_')===0) { unset($vars[$key]); }
		}
		foreach($vars as $key => $val) {
			$this->$key = $val;
		}
		return true;
	}

	// What is the name of this model?
	final function _m() {
		$class = get_class($this);
		return substr($class,strpos($class,'Model_')+6);
	}

	final function __get($name) {
		$id = "{$this->_m()}_id";
		switch ($name) {
			case 'id':
			case $id:
			case '_schemaFields':
			case '_schemaKeys':
			case '_schemaTriggers':
				return $this->$name; break;
			default:
				$trace = debug_backtrace();
				trigger_error('Undefined property: '
					. get_class($this) . '::$' .$name .
					' in ' . $trace[0]['file'] .
					' on line ' . $trace[0]['line'],
					E_USER_NOTICE);
				return NULL; break;
		}
	}

	final function __set($name,$value) {
		$id = "{$this->_m()}_id";
		switch ($name) {
			case 'id':
			case $id: $this->$id = $value; break;
			default:
				if(isset($this->$name)) { return; }
				$this->$name = $value;
				break;
		}
	}

	final protected function _expandSchema() {
		// Create a primary key if it doesn't exist
		if (!array_key_exists('primary',$this->_schemaKeys)
			|| empty($this->_schemaKeys['primary']['fields'])
		) {
			$this->_schemaKeys['primary'] = array(
				'type' => 'primary',
				'fields' => $this->_m().'_id',
			);
		}
		// Create the primary key field if it doesn't exist
		if (is_string($this->_schemaKeys['primary']['fields'])
			&& !array_key_exists(
				$this->_schemaKeys['primary']['fields'],
				$this->_schemaFields
			)
		) {
			$this->_schemaFields = array_merge(
				array($this->_schemaKeys['primary']['fields'] => array(
					'type' =>'id',
					'auto_increment' => TRUE,
				)),
				$this->_schemaFields
			);
		}
		
		// Get field hooks for expansion
		$hooks = Load::Hooks();
		$source_fields = array(
			'class'    => $this->_schemaFields,
			'metadata' => array(), //$hooks->getModelFields($this->_m()),
		);
		
		// Run class fields and hook fields on separate passes
		foreach($source_fields as $source => $fields) {
			foreach($fields as $name => $attrs) {
				// Do not allow metadata to overwrite class fields
				if ($source=='metadata'
					&& array_key_exists($name,$this->_schemaFields)
				) { 
					$trace = debug_backtrace();
					trigger_error('Cannot redeclare schema field: '
						. $name . ' for model ' . $this->_m()
						. ' in ' . $trace[0]['file']
						. ' on line ' . $trace[0]['line'],
						E_USER_NOTICE);
					continue;
				}

				// Convert string value to valid array
				if (is_string($attrs)) {
					$attrs = array('type' => $attrs);
				}
				// Require this field to have a type
				if (!is_array($attrs) || empty($attrs['type'])) {
					continue;
				}

				// Assemble default values
				$default = array();
				switch ($attrs['type']) {
					// Integer types and shorthands
					case 'int': $default['range'] = pow(2,32); break;
					case 'id':
						$default['unsigned'] = TRUE;
						$default['range'] = pow(2,32);
						$attrs['type'] = 'int'; break;
					// String types and shorthands
					case 'string': $default['length'] = 255; break;
					case 'md5':
						$default['range'] = 32;
						$attrs['type'] = 'string'; break;
					case 'resource':
						$default['length'] = 48;
						$attrs['type'] = 'string'; break;
					case 'email':
						$default['length'] = 255;
						$attrs['type'] = 'string'; break;
					// Content types and shorthands
					case 'array':
					case 'content':
						$default['length'] = pow(2,32); break;
				}

				// Merge defaults with attributes
				$attrs = array_merge($attrs,array_diff_key($default,$attrs));

				// Check metadata (except for content fields)
				if (in_array($attrs['type'],array('array','content'))) {
					unset($attrs['metadata']);
				} elseif ($source=='metadata' && !isset($attrs['metadata'])) {
					$attrs['metadata'] = TRUE;
				}

				// Set up default triggers
				if (empty($this->_schemaTriggers['touch_create'])) {
					$touch_create = array_values(
						preg_grep(
							"/^({$this->_m()}_)?created_(at|from|by)$/",
							array_keys($this->_schemaFields)
					));
					if (!empty($touch_create)) {
						$this->_schemaTriggers['touch_create'] = $touch_create;
					}
				}
				if (empty($this->_schemaTriggers['touch_modify'])) {
					$touch_modify = array_values(
						preg_grep(
							"/^({$this->_m()}_)?modified_(at|from|by)$/",
							array_keys($this->_schemaFields)
					));
					if (!empty($touch_modify)) {
						$this->_schemaTriggers['touch_modify'] = $touch_modify;
					}
				}

				// Save expanded schema
				$this->_schemaFields[$name] = $attrs;
			}
		}
	}
}
