<?php

abstract class Model extends EscherObject {
	protected $_schema = array();
	protected $_cache_keys = array();
	protected $_metadata = FALSE;
	protected $_content = array();
	protected $_output_type = 'php';

	public function __construct($key=NULL) {
		parent::__construct();
		// Load hooks for metadata/content
		$hooks = Load::Hooks();
		// If we are allowing all attributes as metadata, hooks don't matter
		if ($this->_metadata!==TRUE) {
			// Otherwise, look for metadata hooks for this model
			if ($metahooks = $hooks->getMetadata($this->_m())) {
				if ($this->_metadata===FALSE) { $this->_metadata = array(); }
				$this->_metadata = array_merge($metahooks,$this->_metadata);
			}
		}

		// Look for content hooks for this model
		if ($contenthooks = $hooks->getContent($this->_m())) {
			if ($this->_content===FALSE) { $this->_content = array(); }
			$this->_content = array_merge($contenthooks,$this->_content);
		}

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
		// Else sort the array keys so caching will work
		} else if (is_array($key) && !empty($key)) {
			ksort($key);
		// And if our key is invalid, return false
		} else { return false; }
		// If we can't load from cache, load from datasources and cache it
		if (!$this->loadCache($key)) {
			if(!$this->loadData($key)) {
				return false;
			} else {
				$this->cache();
			}
		}
		return true;
	}
	
	protected function loadCache($keys) {
		$sources = $this->_getCacheDatasources();
		// Iterate through our datasources, trying to load the model
		foreach($sources as $s) {
			$ds = Load::Datasource($s);
			if ($result = $ds->get($this,$keys)) {
				// Assign class vars (metadata, content, etc.) and return
				$this->assignClassVars($result);
				return true;
			}
		}
		return false;
	}

	protected function loadData($keys) {
		$sources = $this->_getDatasources();
		// Iterate through our datasources, trying to load the model
		foreach($sources as $s) {
			$ds = Load::Datasource($s);
			if ($result = $ds->get($this,$keys)) {
				// When we find the datasource, set it
				$this->_datasource = $s;
				// Assign class vars (metadata, content, etc.)
				$this->assignClassVars($result);
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
	
	function touch($fields=array('c','m'),$model=NULL) {
		if (is_null($model)) { $model = Load::USER(); }
		if (is_a($model,'Model') && !empty($model->id)) {
			$mtype = $model->_m();
			$mid = $model->id;
		} else {
			$mtype = 0;
			$mid = 0;
		}
		$this->_schema();
		foreach($fields as $f) {
			if($f=='c' && isset($this->ctime) && preg_match('/[1-9]/',$this->ctime)) {
				continue;
			}
			$timefield = $f.'time';
			if (array_key_exists($timefield,$this->_schema)) {
				$this->$timefield = date('Y-m-d H:i:s',NOW);
				$idfield = $f.'id';
				if (array_key_exists($idfield,$this->_schema)) {
					$this->$idfield = $mid;
					$typefield = $f.'type';
					if (array_key_exists($typefield,$this->_schema)) {
						$this->$typefield = $mtype;
					}
				}
			}
		}
	}

	// Iterates through an associative array for namespaced form fields and assigns data for this object
	function parseFormData($data,$uniqid=NULL) {
		if (isset($this->id)) {
			$regex = "/^{$this->_m()}_{$this->id}_(.+)$/";
		} elseif (!is_null($uniqid)) {
			$regex = "/^{$this->_m()}_{$uniqid}_(.+)$/";
		} else {
			$fields = array_keys($data);
			foreach ($fields as $k) {
				if (preg_match("/^({$this->_m()}_new[^_]+)_/",$k,$match)) {
					$regex = "/^{$match[1]}_(.+)$/";
					break;
				}
			}
			if (!isset($regex)) { return false; }
		}
		$inputs = preg_grep($regex,array_keys($data));
		if (empty($inputs)) { return false; }
		foreach($inputs as $i) {
			$k = preg_replace($regex,"$1",$i);
			$this->$k = $data[$i];
		}
		return true;
	}

	// Provide the schema of the current model
	function _schema() {
		if (empty($this->_schema)) {
			$sources = $this->_getDatasources();
			foreach($sources as $s) {
				$ds = Load::Datasource($s);
				if ($this->_schema = $ds->getSchema($this->_m())) {
					break;
				}				
			}
		}
		$schema = $this->_schema;
		foreach($this->_content as $v) {
			if (!array_key_exists($v,$schema)) {
				$schema[$v] = 'content';
			}
		}
		if ($this->_metadata===TRUE) {
			$obj = new ArrayObject($this);
			foreach($obj as $k => $v) {
				if (!array_key_exists($k,$schema)) {
					$schema[$k] = 'meta';
				}
			}
		} elseif (is_array($this->_metadata)) {
			foreach($this->_metadata as $v) {
				if (!array_key_exists($v,$schema)) {
					$schema[$v] = 'meta';
				}
			}			
		}
		return $schema;
	}
	
	// provide a list of allowed metadata fields, or TRUE (accepts everything) or FALSE (none)
	function _metadata() {
		return $this->_metadata;	
	}
	
	// Provide a list of allowed content fields, or FALSE if none
	function _content() {
		return $this->_content;	
	}
	
	// Provide a list of cacheable key sets for this model
	function _cache_keys() {
		return $this->_cache_keys;	
	}
	
	// Get the datasources for this object
	protected function _getDatasources() {
		if (isset($this->_datasource)) {
			$sources = array($this->_datasource);
		} else {
			global $CFG;
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
		global $CFG;
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
		if (is_null($data)) {
			$data = get_object_vars($this);
			$data['fieldname_prefix'] = $this->_m().'_'.(isset($this->id) ? $this->id : 'new'.uniqid()).'_';
		}
		if (is_null($type)) { $type = $this->_output_type; }
		$out = Load::Output($type,$this);
		$out->setPath(@$this->path);
		$out->assignVars($data);
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

	// What is the name of this model?
	final function _m() {
		$class = get_class($this);
		return substr($class,strpos($class,'Model_')+6);
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

	// Assign Class Vars (metadata, content, etc.) safely
	protected function assignClassVars($vars) {
		if (is_object($vars)) { $vars = get_object_vars($vars); }
		if (!is_array($vars) || array_values($vars)==$vars) {
			return false;
		}
		if (!empty($vars['_metadata']) && is_array($vars['_metadata'])) {
			if (!$this->_metadata) {
				$this->_metadata = $vars['_metadata'];
			} elseif(is_array($this->_metadata)) {
				$this->_metadata = array_unique(array_merge($this->_metadata,$vars['_metadata']));
			}
		}
		// Merge content intelligently
		if (!empty($vars['_content']) && is_array($vars['_content'])) {
			if(is_array($this->_content)) {
				$this->_content = array_unique(array_merge($this->_content,$vars['_content']));
			} else {
				$this->_content = $vars['_content'];
			}
		}
	}
}