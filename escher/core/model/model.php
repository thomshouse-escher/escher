<?php

abstract class Model extends EscherObject {
	protected $_schemaFields = array();
	protected $_schemaKeys = array();
	protected $_schemaTriggers = array();
	protected $_outputType = 'php';
	protected $_new = TRUE;
	protected $_savedValues = array();

	// Used to keep track of parsed form inputs for new models
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
	
	function loadCached($key) {
		// If this object is already loaded, just reload (ignore provided keys)
		if ($this->id()) { $key = $this->id(); }

		// If only the primary id was provided as key, format for datasource
		if (is_scalar($key) && $this->_primaryKey()) {
			$key = array($this->_primaryKey() => $key);
		}
		// And if our key is invalid, return false
		if (!is_array($key) || empty($key)) { return false; }

		$sources = $this->_getCacheDatasources();
		// Iterate through our datasources, trying to load the model
		foreach($sources as $s) {
			$ds = Load::Datasource($s);
			if ($result = $ds->get($this,$key)) {
				$this->assignVars($result);
				$this->_savedValues = (array)$result;
				$this->_new = FALSE;
				return true;
			}
		}
		return false;
	}

	function loadUncached($key) {
		// If this object is already loaded, just reload (ignore provided keys)
		if ($this->id()) { $key = $this->id(); }

		// If only the primary id was provided as key, format for datasource
		if (is_scalar($key) && $this->_primaryKey()) {
			$key = array($this->_primaryKey() => $key);
		}
		// And if our key is invalid, return false
		if (!is_array($key) || empty($key)) { return false; }

		$sources = $this->_getDatasources();
		// Iterate through our datasources, trying to load the model
		foreach($sources as $s) {
			$ds = Load::Datasource($s);
			if ($result = $ds->get($this,$key)) {
				// When we find the datasource, set it
				$this->_datasource = $s;
				// Assign class vars (metadata, content, etc.)
				$this->assignVars($result);
				$this->_savedValues = (array)$result;
				$this->_new = FALSE;
				return true;
			}
		}
		return false;
	}
	
	public function save() {
		// Touch the model if this is the first save
		if ($this->_primaryKey() && !$this->id()) {
			$this->_runTriggers(array('create','touch'));
		} else {
			$this->_runTriggers('modify');
		}

		foreach($this->_schemaFields as $k => $p) {
			if (!isset($this->$k)
				&& array_key_exists($k,$this->_savedValues)
			) {
				$this->$k = '';
			}
		}

		// Set datasource options
		$options = array(
			'mode' => $this->_new
				? 'insert'
				: 'update'
		);

		$sources = $this->_getDatasources();
		// Iterate through the datasources and save
		// Note: Only new objects should have to iterate
		foreach($sources as $s) {
			$ds = Load::Datasource($s);
			if ($id = $ds->set($this,NULL,$options)) {
				$this->_new = FALSE;
				if ($this->_primaryKey() && !$this->id()) {
					$this->setValues(array(
						$this->_primaryKey() => $id,
					));
				}
				if (!isset($this->_datasource)) {
					$this->_datasource = $s;
				}
				$this->expire();
				$this->notifyObservers('save');
				$this->_savedValues = $this->getValues();
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
	
	function touch($fields=NULL,$model=NULL,$create=FALSE) {
		// If no fields provided, try the triggers
		if (empty($fields)) {
			$this->_runTriggers('touch',$model);
			return;
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
			if($create && !empty($this->$f)) {
				continue;
			}
			switch ($this->_schemaFields[$f]['type']) {
				case 'datetime': $this->$f = date('Y-m-d H:i:s',NOW); break;
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
		} else {
			$sources = $CFG['datasource_cache_order']['all'];
		}
		return $sources;
	}

	// Display a view for this model, using provided data or object data
	function display($view,$data=NULL,$type=NULL) {
		if (is_null($type)) { $type = $this->_outputType; }
		$out = Load::Output($type);
		if (is_null($data)) {
			$nameFormat = !empty($this->id)
				? "model[{$this->_m()}][{$this->id}][%s]"
				: "model[{$this->_m()}][new][".uniqid()."][%s]";
			$out->assignVars($this->getValues());
			$out->assignModelVars($this,$nameFormat);
		} else {
			$out->assignVars($data);
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
			if ($result = $ds->get($this,$conditions,$options)) {
				return $result;
			}
		}
		return false;
	}

	// Override assignVars to protect class variables
	public function assignVars($vars) { return $this->setValues($vars); }

	public function setValues($vars) {
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

	public function getValues() {
		$values = array();
		foreach($this->_schemaFields as $k => $props) {
			if (isset($this->$k)) {
				$values[$k] = $this->$k;
			}
		}
		return $values;
	}

	function id() {
		return $this->{$this->_primaryKey()};
	}

	function _primaryKey() {
		if (array_key_exists('primary',$this->_schemaKeys)
			&& sizeof($this->_schemaKeys['primary']['fields'])==1
		) {
			return reset($this->_schemaKeys['primary']['fields']);
		} else {
			return "{$this->_m()}_id";
		}
	}

	// What is the name of this model?
	final function _m() {
		$class = get_class($this);
		return substr($class,strpos($class,'Model_')+6);
	}

	final function __get($name) {
		$id = $this->_primaryKey();
		switch ($name) {
			case 'id':
				return $this->$id; break;
			case $id:
			case '_schemaFields':
			case '_schemaKeys':
			case '_schemaTriggers':
			case '_savedValues':
				return isset($this->$name)
					? $this->$name
					: NULL;
				break;
			default:
				if (array_key_exists($name,$this->_schemaFields)) {
					$this->$name = NULL;
				} else {
					$trace = debug_backtrace();
					trigger_error('Undefined property: '
						. get_class($this) . '::$' .$name .
						' (from ' . $trace[0]['file'] .
						' on line ' . $trace[0]['line'] . ')',
						E_USER_NOTICE);
				}
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

	final function __unset($name) {
		$id = "{$this->_m()}_id";
		switch ($name) {
			case 'id': unset($this->$id); break;
		}
	}

	final function __isset($name) {
		if ($name=='id') {
			$id = "{$this->_m()}_id";
			return isset($this->$id);
		}
		return false;
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
			'metadata' => $hooks->getSchemaFields($this->_m()),
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
					case 'int': $default['range'] = pow(2,32)-1; break;
					case 'id':
						$default['unsigned'] = TRUE;
						$default['range'] = pow(2,32);
						$attrs['type'] = 'int'; break;
					// String types and shorthands
					case 'string': $default['length'] = 255; break;
					case 'md5':
						$default['length'] = 32;
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
						$default['length'] = pow(2,32)-1; break;
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
						$this->_schemaTriggers['touch_create'] = array(
							'fields' => $touch_create,
						);
					}
				}
				if (empty($this->_schemaTriggers['touch_modify'])) {
					$touch_modify = array_values(
						preg_grep(
							"/^({$this->_m()}_)?modified_(at|from|by)$/",
							array_keys($this->_schemaFields)
					));
					if (!empty($touch_modify)) {
						$this->_schemaTriggers['touch_modify'] = array(
							'fields' => $touch_modify,
						);
					}
				}

				// Save expanded schema
				$this->_schemaFields[$name] = $attrs;
			}
		}

		// Clean up the keys
		foreach($this->_schemaKeys as $k => $v) {
			$this->_schemaKeys[$k]['fields'] = (array)$v['fields'];
		}

		// Initialize fields
		foreach($this->_schemaFields as $k => $v) {
			$this->$k = isset($v['default']) ? $v['default'] : NULL;
		}
	}

	final protected function _runTriggers($event) {
		$args = array_slice(func_get_args(),1);
		// Run triggers for each event by passing recursively
		if (is_array($event)) {
			$result = TRUE;
			foreach($event as $e) {
				$result = call_user_func_array(
					array($this,'_runTriggers'),
					array_merge(array($e),$args)
					) && $result;
			}
			return $result;
		}

		// Run each event
		$result = TRUE;
		foreach($this->_schemaTriggers as $name => $trigger) {
			switch ($name) {
				case 'touch_create':
					if ($event=='touch') {
						$result = $this->touch(
							$trigger['fields'],
							!empty($args) ? reset($args) : NULL,
							TRUE
						) && $result;
					}
					break;
				case 'touch_modify':
					if ($event=='touch') {
						$result = $this->touch(
							$trigger['fields'],
							!empty($args) ? reset($args) : NULL
						) && $result;
					}
					break;
				default:
					if (!empty($trigger['event'])
						&& in_array($event,(array)$trigger['event'])
						&& !empty($trigger['action'])
						&& method_exists($this,$trigger['action'])
					) {
						$params = !empty($trigger['fields'])
							? $trigger['fields']
							: array();
						array_unshift($args,$params);
						$result = call_user_func_array(
							array($this,$trigger['action']),
							$args
						) && $result;
					}
					break;
			}
		}
		return $result;
	}
}
