<?php

class Model_route_static extends EscherObject {
	public function __construct($key=NULL) {
		parent::__construct();
		$this->id = $key;
		if ($this->id=='/') {
			$this->parent_id = '';
		} else {
			$this->parent_id = '/'.implode('/',array_slice(preg_split('#/#',$this->id,-1,PREG_SPLIT_NO_EMPTY),0,-1));
		}
	}
	
	public function getParent() {
		if(empty($this->parent_id)) {
			return false;
		}
		$parent = clone $this;
		$parent->id = $parent->parent_id;
		if ($parent->id=='/') {
			$parent->parent_id = '';
		} else {
			$parent->parent_id = '/'.implode('/',array_slice(preg_split('#/#',$parent->id,-1,PREG_SPLIT_NO_EMPTY),0,-1));
		}
		return $parent;
	}
}