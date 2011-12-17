<?php

class Helper_router_dynamic extends Helper_router {

	protected function findRoute() {
		if ($result = parent::findRoute()) { return $result; }

		// Load the config
		$CFG = Load::CFG();

		// Dynamic routes don't work in maintenance mode
		if (!empty($CFG['maintenance_mode'])) {
			// ...unless we have special permissions
			$acl = Load::ACL();
			if (!$acl->req('all',array('maintenance_mode','sysadmin'),'/')) {
				return false;
			}
		} // End maintenance mode

		$route['id'] = $CFG['root']['id'];
		$current = $parent = $site = array();
		$args = explode('/',$this->path);
		while (!empty($args)
			&& $m = Load::Model('route_dynamic',array(
				'parent_id'=>$route['id'],'tag'=>$args[0]))
		) {
			$model = $m;
			$parent = $current;
			$current[] = array_shift($args);

			if (!empty($m->theme)) { $route['theme'] = $m->theme; }
			if (!empty($m->subsite)) { $site = $current; }

			$route['id'] = $m->id;
			$route['route_ids'][] = $m->id;
		}
		
		if (empty($current)) { return false; }

		$route['current_path'] = implode('/',$current);
		$route['parent_path'] = implode('/',$parent);
		$route['site_path'] = implode('/',$site);
		$route['controller'] = $model->controller;
		if (!empty($model->instance_id)) { $route['instance_id'] = $model->instance_id; }
		$route['title'] = $model->title;
		$route['args'] = $args;
		$route['route'] = $model;

		return $route;
	}

	function getPathByInstance($controller,$id=NULL,$absolute=TRUE) {
		$result = parent::getPathByInstance($controller,$id,$absolute);
		if (!empty($result)) { return $result; }
		
		$m = Load::Model('route_dynamic',
			array('controller'=>$controller, 'instance_id'=>$id));
		if (empty($m)) { return false; }
		
		$path = array($m->tag);

		while (!empty($m->parent_id)) {
			$m = Load::Model('route_dynamic',$m->parent_id);
			if (!empty($m->parent_id)) { array_unshift($path, $m->tag); }
		}
		
		$path = implode('/',$path);

		return $this->getRootPath($absolute)."/$path";
	}

	function getPathById($id,$absolute=TRUE) {
		$m = Load::Model('route_dynamic',$id);
		if (empty($m)) { return false; }
		
		$path = array($m->tag);

		while (!empty($m->parent_id)) {
			$m = Load::Model('route_dynamic',$m->parent_id);
			if (!empty($m->parent_id)) { array_unshift($path, $m->tag); }
		}
		
		$path = implode('/',$path);

		return $this->getRootPath($absolute)."/$path";
	}
}