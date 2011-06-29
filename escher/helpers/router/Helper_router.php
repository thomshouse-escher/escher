<?php

abstract class Helper_router extends Helper {

	function __construct($args) {
		if (is_scalar($args)) {
			$args = array('path' => $args);	
		}
		parent::__construct($args);
		$this->findRoute();
	}
	
	function getPath($absolute=TRUE) {
		if ($absolute) {
			global $CFG;
			$root = $CFG['wwwroot'];
		} else {
			$root = '';
		}
		$path;
		$path->current = $root;
		if (!empty($this->current_path)) {
			$path->current .= '/'.$this->current_path;
		}
		if (isset($this->parent_path)) {
			$path->parent = $root;
			if(!empty($this->parent_path)) {
				$path->parent .= '/'.$this->parent_path;
			}
		} else {
			$path->parent = NULL;
		}
		return $path;
	}
	
	function getContext() {
		return $this->context;
	}

	protected function findRoute() {
		global $CFG;
		$routes = (array)@$this->static_routes;
		if (empty($routes)) {
			$hooks = Load::Hooks();
			$routes = array_merge($CFG['static_routes'],$hooks->getStaticRoutes());
		}
		if (is_array($CFG['predefined_routes'])) {
			$routes = array_merge($CFG['predefined_routes'],$routes);
		}
		$route = array();
		if (!empty($this->path)) {
			foreach($routes as $key => $route) {
				$rpreg = preg_replace(
					array('#/?\[(num|id)\]/?#','#/?\[word\]/?#','#/?\[tag\]/?#','#/\*/?$#','#\*/?#','#/$#','#^/#'),
					array('/(\d+)/','/([a-zA-Z]+)/','/([\w-]+)/','(/.+)?','([^\/])/','',''),$key);
				if (preg_match("@^/?{$rpreg}(/?.*)/?$@",$this->path,$matches)) {
					$route['pattern'] = $key;
					break;
				}
			}
		}
		if (empty($matches)) {
			global $CFG;
			$args = preg_split('#/#',$this->path,-1,PREG_SPLIT_NO_EMPTY);
			$route = $CFG['root'];
			$route['current_path'] = '';
			if (isset($route['args']) && is_array($route['args'])) {
				$route['args'] = array_merge($route['args'],$args);
			} else {
				$route['args'] = $args;
			}
		} else {
			if (!isset($route['current_path'])) {
				$pattr = preg_split('#/#',preg_replace('#/((\[[^\]]\]|\*)/?)$#','/',
					$route['pattern']),-1,PREG_SPLIT_NO_EMPTY);
				$pathr = preg_split('#/#',$this->path,-1,PREG_SPLIT_NO_EMPTY);
				$route['current_path'] = implode('/',array_slice($pathr,0,sizeof($pattr)));
			}
			if (!isset($route['parent_path'])) {
				$route['parent_path'] = implode('/',array_slice(explode('/',$route['current_path']),0,-1));
			}

			array_shift($matches);
			if (empty($matches[0])) { array_shift($matches); }
			if (!empty($matches)) {
				if (strpos($matches[sizeof($matches)-1],'/')!==FALSE) {
					$remaining_args = array_pop($matches);
					$remaining_args = preg_split('#/#',$remaining_args,-1,PREG_SPLIT_NO_EMPTY);
					$matches = array_merge($matches,$remaining_args);
				}
			}
			$route['args'] = $matches;
		}
		$this->assignVars($route);
		$this->context = Load::Model('route_static','/'.$this->current_path);
		return true;
	}
}