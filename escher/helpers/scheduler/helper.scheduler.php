<?php

class Helper_scheduler extends Helper {
	public static $shutdown = 0;

	function add($controller,$method,$data=array(),$resource=NULL,$time=NULL) {
		// Sanitize resource
		if (is_a($resource,'Model')) {
			$r_type = $resource->_m();
			$r_id = $resource->id();
		} elseif (is_array($resource) && sizeof($resource)==2) {
			$r_type = reset($resource);
			$r_id = next($resource);
		} else {
			$r_type = '';
			$r_id = 0;
		}

		// Get plugin (if present)
		if (is_array($controller)) {
			list($plugin,$controller) = $controller;
		} else {
			$plugin = '';
		}

		// Assemble the array of variables
		$vars = array(
			'plugin'     => $plugin,
			'controller' => $controller,
			'method'     => $method,
			'resource_type' => $r_type,
			'resource_id'   => $r_id,
			'data'       => $data,
		);
		if (!is_null($time)) {
			$vars['process_at'] = $time;
		}

		// Save the job
		$job = Load::Model('scheduler_job');
		$job->assignVars($vars);
		$job->save();

		// If process_at is not in the future, start the process
		if ((is_null($time) || $time <= NOW) && self::$shutdown==0) {
			self::$shutdown = 1;
			register_shutdown_function(array($this,'process'));
		}
	}

	function process($time=0) {
		if (is_numeric($time) && $time>0) {
			$time = round($time);
		} else {
			$time = 0;
		}
		$CFG = Load::Config();
		pclose(popen("php {$CFG['document_root']}/proc.php - scheduler process $time > /dev/null &",'r'));
	}
}