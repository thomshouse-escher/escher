<?php

class Helper_output_php extends Helper_output {
	public $extension = '.php';
	
	function __construct($args=array()) {
		parent::__construct($args);
		include_once(dirname(__FILE__).'/functions.php');
	}
	
	function fetch($filename) {
		$filename .= $this->extension;
		if (!file_exists($filename)) {
			return false;
		}
		
		// Setup Function Shorthands
		$E = $_echo = '__esc_output_php_echo';
		$F = $_filter = '__esc_output_php_filter';
		$H = $_hook = '__esc_output_run_event';
		$L = $_lang = $E; //Temporary, until localizations are implemented
		$_browser = '__esc_output_php_match_browser';
		$_check = '__esc_output_acl_check';
		$_require = '__esc_output_acl_require';
		$_unload = '__esc_output_clear_unload';
		$HTML = Load::Helper('html','auto');
		$UI = Load::UI();
		$HTML->directOutput(TRUE);
		$UI->directOutput(TRUE);

		// Assign and filter vars
		$this->assignReservedVars();
		$filter_object = Load::Filter();
		extract($filter_object->filter($this->getAssignedVars(),$this->var_filter));
		unset($filter_object);
		$hooks = Load::Hooks();
		extract($hooks->getOutputFunctions());
		$this->__old_cwd = getcwd();
		$this->__old_err = error_reporting(0);
		ob_start();
		chdir(dirname($filename));
		include $filename;
		chdir($this->__old_cwd);
		error_reporting($this->__old_err);
		$UI = Load::UI();
		$UI->directOutput(FALSE);
		return ob_get_clean();
	}	

	protected function embed($view) {
		echo $this->display($view);
	}
}