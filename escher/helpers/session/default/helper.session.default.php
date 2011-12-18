<?php

class Helper_session_default extends Helper_session {
	protected $useCustomHandler = FALSE;
	
	function __construct() {
	//ini_set('session.gc_maxlifetime',$this->daysToPersist*60*60*24);
		parent::__construct();
	}
}