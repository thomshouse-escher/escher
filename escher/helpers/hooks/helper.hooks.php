<?php

class Helper_hooks extends Helper {
	protected $dispatches = array();
	protected $outputFunctions = array();
	protected $userauths = array();
	protected $events = array();
	protected $filters = array();
	protected $models = array();
	protected $schemaFields = array();
	protected $staticroutes = array();

	function registerDispatch($dispatch,$controller) {
		if (array_key_exists($dispatch,$this->dispatches)) {
			Load::Error('500');
		}
		$this->dispatches[$dispatch] = $controller;
	}

	function getDispatch($dispatch) {
		if (array_key_exists($dispatch,$this->dispatches)) {
			return $this->dispatches[$dispatch];
		}
		return false;
	}

	function registerEvent($event,$callback,$priority=0) {
		$priority = (int)$priority;
		$this->events[$event][$priority][] = $callback;
		return true;
	}
	
	function runEvent($event,$args=array()) {
		if (!array_key_exists($event,$this->events)) { return array(); }
		$callbacks = $this->events[$event];
		ksort($callbacks);
		$result = array();
		foreach($callbacks as $priority) {
			foreach($priority as $callback) {
				$callname = $callback;
				if (is_array($callname)) {
					if (is_object($callname[0])) {
						$callname[0] = get_class($callname[0]);
					}
					$callname = implode('::',$callname);
				}
				$result[$callname] = call_user_func_array($callback,$args);
			}
		}
		return $result;
	}

	function registerFilter($filter,$callback,$priority=0) {
		$priority = (int)$priority;
		$this->filters[$filter][$priority][] = $callback;
		return true;
	}
	
	function runFilter($filter,$value) {
		if (!array_key_exists($filter,$this->filters)) { return $value; }
		$callbacks = $this->filters[$filter];
		ksort($callbacks);
		foreach($callbacks as $pr) {
			foreach($pr as $callback) {
				$value = call_user_func($callback,$value);
			}
		}
		return $value;
	}

	function registerSchemaFields($model,$fields) {
		if (!is_string($model)) return false;
		$fields = (array)$fields;
		foreach ($fields as $k => $v) {
			if (!is_string($v)) {
				unset($fields[$k]);
			}
		}
		if (empty($fields)) { return false; }
		if (array_key_exists($model,$this->schemaFields)) {
			$this->schemaFields[$model] = array_merge(
				$fields,
				$this->schemaFields[$model]
			);
		} else {
			$this->schemaFields[$model] = $fields;
		}
		return true;
	}
	
	function getSchemaFields($model) {
		if (!is_string($model)) return false;
		if (!isset($this->schemaFields[$model])) {
			return array();
		} else {
			return $this->schemaFields[$model];
		}
	}

	function registerModelPlugin($model,$plugin='') {
		if (is_array($model)) {
			$result = TRUE;
			foreach($model as $m) {
				$result = $result && $this->registerModelPlugin($m,$plugin);
			}
			return $result;
		}
		if(!is_string($model) || !is_string($plugin)) {
			return false;
		} elseif(array_key_exists($model,$this->models)) {
			Load::Error('500');
		}
		$this->models[strtolower($model)] = strtolower($plugin);
		return true;
	}
	
	function getModelPlugin($model) {
		if (!is_string($model)) { return false; }
		$model = strtolower($model);
		if (array_key_exists($model,$this->models) && !empty($this->models[$model])) {
			return $this->models[$model];
		} else { return false; }
	}
	
	function registerOutputFunction($funcname,$realname) {
		if(!is_string($funcname) || !is_string($realname)) {
			return false;
		}
		$this->outputFunctions[$funcname] = $realname;
		return true;
	}
		
	function getOutputFunctions() {
		return $this->outputFunctions;
	}

	function registerUserAuth($authname,$plugin,$helper,$args=NULL) {
		if(!is_string($authname) || !is_string($plugin) || !is_string($helper)) {
			return false;
		}
		$this->userauths["$authname"] = array($plugin,$helper,$args);
		return true;
	}

	function getUserAuths() {
		return $this->userauths;
	}

	function registerStaticRoute($path,$options) {
		if(is_string($options)) { $options = array('controller' => $options); }
		if(!is_string($path) || !is_array($options) ||
			!array_key_exists('controller',$options) ||
			array_key_exists($path,$this->staticroutes)) {
				return false;
		}
		$this->staticroutes[$path] = $options;
	}

	function getStaticRoutes() {
		return $this->staticroutes;
	}
	
	function loadPluginHooks($plugins) {
		if (!is_array($plugins)) return false;
		$CFG = Load::Config();
		foreach($plugins as $p) {
			Load::inc($CFG['document_root']."/plugins/$p/plugin.$p.php");
			$classname = "Plugin_$p";
			if (class_exists($classname)) {
				$po = new $classname;
				$po->loadHooks();
			}
		}
	}
}
