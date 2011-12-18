<?php

class EscherObject {
	function __construct() {
		
	}
	
	// Assign an associative array of variables to this object
	public function assignVars($vars) {
		if (!is_array($vars) || array_values($vars)==$vars) {
			return false;
		}
		foreach($vars as $key => $val) {
			$this->$key = $val;
		}
		return true;
	}
	
	// What plugin (if any) does this object come from?
	public function _plugin() {
		if ($this) { $class = get_class($this); }
		else { $class=get_class(self); }
		if (preg_match('/Plugin_([a-z\d_]+)_[A-Z]/',$class,$match)) {
			return($match[1]);
		} else { return false; }
	}
}