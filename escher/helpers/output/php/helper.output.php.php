<?php

class Helper_output_php extends Helper_output {
	public $extension = '.php';
	static protected $instances = array();
	
	function __construct($args=array()) {
		parent::__construct($args);
		include_once(dirname(__FILE__).'/functions.php');
	}
	
	function fetch($filename) {
		array_unshift(self::$instances,$this);
		$filename .= $this->extension;
		if (!file_exists($filename)) {
			return false;
		}
		
		// Setup Function Shorthands
		$E = $_echo = 'escher_echo';
		$F = $_filter = 'escher_filter';
		$H = $_hook = 'escher_run_event';
		$U = $_url = 'escher_resolve_path';
		$L = $_lang = $E; //Temporary, until localizations are implemented
		$_browser = 'escher_match_browser';
		$_check = 'escher_acl_check';
		$_require = 'escher_acl_require';
		$_unload = 'escher_clear_unload';
		$HTML = Load::Helper('html','auto');
		$FORM = Load::Helper('form');
		if (!empty($this->modelVars)) {
			$FORM->setData((array)$this->modelVars[0]);
			$FORM->setNameFormat($this->modelVars[1]);
		}
		$UI = Load::UI();
		$headers = Load::Headers();
		$HTML->directOutput(TRUE);
		$FORM->directOutput(TRUE);
		$UI->directOutput(TRUE);

		// Assign and filter vars
		$this->assignReservedVars();
		$filter_object = Load::Filter();
		extract($filter_object->filter($this->getAssignedVars(),$this->var_filter));
		unset($filter_object);
		$hooks = Load::Hooks();
		extract($hooks->getOutputFunctions());
		$this->__old_cwd = getcwd();
		$this->__old_err = error_reporting(E_ERROR | E_WARNING | E_PARSE);
		ob_start();
		chdir(dirname($filename));
		include $filename;
		chdir($this->__old_cwd);
		error_reporting($this->__old_err);
		$UI = Load::UI();
		$UI->directOutput(FALSE);
		array_shift(self::$instances);
		return ob_get_clean();
	}	

	protected function embed($view) {
		echo $this->display($view);
	}

	public static function getCurrent() {
		return self::$instances[0];
	}
}