<?php

abstract class Helper_output extends Helper {
	public $extension = '';
	public $var_filter = 'encode';
	protected $assigned_vars = array();
	protected $path;

	abstract function fetch($filename);
	
	function __construct($args=array()) {
		parent::__construct($args);
		$this->headers = Load::Headers();
	}
	
	function assign($name,$val) {
		$this->assigned_vars[$name] = $val;
	}
	
	function assignVars($arr) {
		if (is_array($arr)) {
			$this->assigned_vars = array_merge($this->assigned_vars,$arr);
		}
	}
	
	function assignReservedVars() {
		$router = Load::Router();
		$useragent = Load::Helper('useragent','default');
		$this->assign('www',$router->getRootPath());
		Load::UserAgent();
		$this->assign('useragent_classes',implode(' ',$useragent->getClasses()));
		$this->assign('USER',Load::User());
		$this->assign('current_path',$router->getCurrentPath());
		$this->assign('parent_path',$router->getParentPath());
	}
	
	function getAssignedVars() {
		return $this->assigned_vars;
	}
	
	function display($view) {
		$this->assignReservedVars();
		return $this->fetch($view);
	}

	function displayControllerView($controller,$view) {
		$CFG = Load::CFG();
		if (is_a($controller,'Controller')) {
			$plugin = @$controller->_plugin();
			$controller = $controller->_c();
		} elseif (is_array($controller)) {
			$plugin = $controller[0];
			$controller = $controller[1];
		} elseif (is_string($controller)) {
			$plugin = FALSE;
		} else { return false; }
		if (!empty($plugin)) {
			$plugin_dir = '/plugins/'.$plugin;
		} else {
			$plugin_dir = '/escher/';
		}
		$controller_dir = $plugin_dir.'/controllers/'.$controller;
		$this->assign('plugin_dir',$CFG['wwwroot'].$plugin_dir);
		$this->assign('controller_dir',$CFG['wwwroot'].$controller_dir);
		$this->assignReservedVars();
		return $this->fetch($CFG['fileroot']."/$controller_dir/views/$view");
	}	
	
	function displayModelView($model,$view) {
		$CFG = Load::CFG();
		if (is_a($model,'Model')) {
			$plugin = @$model->_plugin();
			$model = $model->_m();
		} elseif (is_array($model)) {
			$plugin = $model[0];
			$model = $model[1];
		} elseif (is_string($model)) {
			$plugin = FALSE;
		} else { return false; }
		if (!empty($plugin)) {
			$plugin_dir = '/plugins/'.$plugin;
		} else {
			$plugin_dir = '/escher/';
		}
		$model_dir = $plugin_dir.'/models/'.$model;
		$this->assign('plugin_dir',$CFG['wwwroot'].$plugin_dir);
		$this->assign('model_dir',$CFG['wwwroot'].$model_dir);
		$this->assignReservedVars();
		return $this->fetch($CFG['fileroot']."$model_dir/views/$view");
	}	
	
	function displayTheme($theme,$content) {
		$CFG = Load::CFG();
		if (is_array($theme)) {
			$plugin = $theme[0];
			$theme = $theme[1];
		} elseif (is_string($theme)) {
			$plugin = FALSE;
		} else { return false; }
		if (!empty($plugin)) {
			$plugin_dir = '/plugins/'.$plugin;
		} else {
			$plugin_dir = '/escher/';
		}
		$theme_dir = $plugin_dir.'/themes/'.$theme;
		include_once($CFG['fileroot']."$theme_dir/theme.php");
		$headers = Load::Headers();
		if (!$notifications = $headers->getNotifications()) {
			$notifications = array();
		}
		$out = Load::Output($type);
		$out->assignVars($this->getAssignedVars());
		$out->assign('plugin_dir',$CFG['wwwroot'].$plugin_dir);
		$out->assign('theme_dir',$CFG['wwwroot'].$theme_dir);
		$out->assign('notifications',$notifications);
		$out->assign('CONTENT',$content);
		return $out->display($CFG['fileroot']."$theme_dir/index");
	}	
	
	function doEcho($text,$default='') {
		echo (!empty($default) && empty($text)) ? $default : $text;
	}

	function clearUnload() {
		static $unload;
		if (is_null($unload)) {
			$unload = 1;
			$headers = Load::Headers();
			$headers->addHeadHTML('
<script type="text/javascript" language="javascript">
	
	window.onbeforeunload = function() {
		return "Are you sure you wish to navigate away from this page?  You may lose unsaved changes.";		
	}
	
</script>');
		}
		return 'window.onbeforeunload = null;';
	}
}