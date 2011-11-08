<?php

class EscherHelper extends EscherObject {
	protected $init_args = array();

	function __construct($args=array()) {
		parent::__construct();
		if (is_object($args)) {
			$args = get_object_vars($args);
		}
		$this->init_args = $args;
		return $this->assignVars($args);
	}
}