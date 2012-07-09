<?php

class Model_route_static extends Model {
	protected $_schemaFields = array(
		'parent_id'   => 'id',
		'title'       => 'string',
		'controller'  => 'resource',
		'instance_id' => 'id',
		'subsite'     => 'bool',
		'theme'       => 'resource',
	);
	protected $_schemaKeys = array(
		'primary'    => array(
			'type' => 'primary',
			'fields' => 'route_id',
		),
	);

	public function __construct($key=NULL) {
		parent::__construct();
		$this->route_id = $key;
		$config = Load::Config();
		if ($this->route_id=='/') {
			$this->_savedValues = array('route_id' => '');
		} else {
			if (array_key_exists(ltrim($key,'/'),$config['static_routes'])) {
				$this->_savedValues = $config['static_routes'][ltrim($key,'/')];
			}
			$this->parent_id = '/'.implode('/',array_slice(preg_split('#/#',$this->route_id,-1,PREG_SPLIT_NO_EMPTY),0,-1));
		}
		$this->setValues($this->_savedValues);
	}
	
	public function getParent() {
		if(empty($this->parent_id)) {
			return false;
		}
		$parent = clone $this;
		$parent->route_id = $parent->parent_id;
		if ($parent->route_id=='/') {
			$parent->parent_id = '';
		} else {
			$parent->parent_id = '/'.implode('/',array_slice(preg_split('#/#',$parent->route_id,-1,PREG_SPLIT_NO_EMPTY),0,-1));
		}
		return $parent;
	}

	function id() { return isset($this->route_id) ? $this->route_id : FALSE; }
	function _primaryKey() { return 'route_id'; }

	function load() { }
	function loadCached() { }
	function loadUncached() { }
	function save() { }
	function cache() { }
	function delete() { }
	function expire() { }
	function touch() { }
	function parseInput() { }
	function find() { }
}