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
		// Force $controller to be an actual Controller object
		if (is_array($controller) || is_string($controller)) {
			$controller = Load::Controller($controller);
		}
		if (!is_a($controller,'Controller')) { return false; }

		// Find a path to a valid view file or return false
		if (!$view_path = $this->getViewPath($controller,$view)) {
			return false;
		}

		// Fetch the view
		$this->assignReservedVars();
		return $this->fetch($view_path);
	}	
	
	function displayModelView($model,$view) {
		// Force $model to be an actual Model object
		if (is_array($model) || is_string($model)) {
			$model = Load::Model($model);
		}
		if (!is_a($model,'Model')) { return false; }

		// Find a path to a valid view file or return false
		if (!$view_path = $this->getViewPath($model,$view)) {
			return false;
		}

		// Fetch the view
		$this->assignReservedVars();
		return $this->fetch($view_path);
	}	
	
	function displayTheme($theme,$content) {
		// Determine plugin and theme
		if (is_array($theme)) {
			$plugin = $theme[0];
			$theme = $theme[1];
		} elseif (is_string($theme)) {
			$plugin = FALSE;
		} else {
			return false;
		}

		// Determine plugin/core dir and theme dir
		if (!empty($plugin)) { $plugin_dir = '/plugins/'.$plugin; }
		else { $plugin_dir = '/escher/'; }
		$theme_dir = $plugin_dir.'/themes/'.$theme;

		// Load Config (for fileroot)
		$CFG = Load::Config();

		// Get the real output helper
		include_once($CFG['fileroot']."$theme_dir/theme.php");
		$out = Load::Output($type);

		// Assign variables
		$out->assignVars($this->getAssignedVars());
		$out->assign('plugin_dir',$CFG['fileroot'].$plugin_dir);
		$out->assign('theme_dir',$CFG['fileroot'].$theme_dir);
		$out->assign('CONTENT',$content);

		// Assign notifications if present
		$headers = Load::Headers();
		if (!$notifications = $headers->getNotifications()) {
			$notifications = array();
		}
		$out->assign('notifications',$notifications);

		// Display
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

	protected function getViewPath($object,$view) {
		$ref = new ReflectionClass(get_class($object));
		while ($ref && $ref->getShortName() != 'EscherObject') {
			$path = dirname($ref->getFileName())."/views/$view";
			if (file_exists("$path{$this->extension}")) {
				return $path;
			}
			$ref = $ref->getParentClass();
		}
		return false;
	}
}