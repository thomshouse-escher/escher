<?php

class EscherObject {
	protected $_observers = array();

	function __construct() { }

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

	public function attachObserver($object) {
		if (is_a($object,'EscherObject')) {
			$this->detachObserver($object);
			$this->_observers[] = $object;
		}
	}

	public function detachObserver($object) {
		if (is_a($object,'EscherObject')) {
			foreach($this->_observers as $k => $o) {
				if ($o===$object) {
					unset($this->_observers[$k]);
				}
			}
		}
	}

	public function notifyObservers($event=NULL) {
		foreach($this->_observers as $o) {
			$o->onObservation($this,$event);
		}
	}

	public function observeObject($object) {
		if (is_a($object,'EscherObject')) {
			$object->attachObserver($this);
		}
	}

	public function onObservation($object,$event=NULL) { }

	// What plugin (if any) does this object come from?
	public function _plugin() {
		if ($this) { $class = get_class($this); }
		else { $class=get_class(self); }
		if (preg_match('/Plugin_([a-z\d_]+)_[A-Z]/',$class,$match)) {
			return($match[1]);
		} else { return false; }
	}
}