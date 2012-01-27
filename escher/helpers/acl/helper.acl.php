<?php

/**
 * Helper_acl.php
 * 
 * ACL (Access Control List) Helper base class
 * @author Thom Stricklin <code@thomshouse.net>
 * @version 1.0
 * @package Escher
 * @subpackage Helpers
 */

/**
 * ACL (Access Control List) Helper base class
 * @author Thom Stricklin <code@thomshouse.net>
 * @version 1.0
 * @package Escher
 * @subpackage Helpers
 */
class Helper_acl extends Helper {
	/**
	 * Checks if an entity has access (generic or specific) to a resource.
	 * Wrapper for req() providing default options where applicable.
	 * Sans arguments, will do a loose check of the current user in the current context.
	 * @param mixed $resource
	 * @param mixed $action
	 * @param mixed $context
	 * @param boolean $inherit
	 * @param mixed $entity
	 * @return boolean
	 */
	function check($resource='all',$action=array(),$context='./',$inherit=TRUE,$entity=NULL) {
		if ($resource=='all' || is_null($resource)) {
			$resource = array('all');
		} else {
			if ($resource = $this->sanitizeModel($resource)) {
				// If $resource is valid, add 'all' to the resource type
				$resource = array('all',$resource);
			// If $resource is not valid, return false
			} else { return false; }
		}
		// Cast action to array and add 'all' 
		$action = (array)$action;
		$action[] = 'all';
		// ...and pass it all along to require (since this is just a kinder, gentler version)
		return $this->determineAccess($resource,$action,$context,$inherit,$entity);
	}

	/**
	 * Checks if an entity has access (specific) to a resource.
	 * Sanitizes inputs and passes to determineAccess() to perform low-level logic.
	 * @param mixed $resource
	 * @param mixed $action
	 * @param mixed $context
	 * @param boolean $inherit
	 * @param mixed $entity
	 * @return boolean
	 */
	function req($resource,$action=array(),$context='./',$inherit=TRUE,$entity=NULL) {
		// Sanitize $resource
		if ($resource = $this->sanitizeModel($resource)) {
			$resource = array($resource);
		} else { return false; }
		// Sanitize $action
		if (empty($action)) { return false; }
		$action = (array)$action;
		// This is where the magic will happen
		return $this->determineAccess($resource,$action,$context,$inherit,$entity);
	}
	
	/**
	 * Low-level permission checking.  Checks recursively.  Deny > Allow.  Entity > Group > All entities.
	 * @param mixed $resource
	 * @param mixed $action
	 * @param mixed $context
	 * @param boolean $inherit
	 * @param mixed $entity
	 * @return boolean
	 */
	protected function determineAccess($resource,$action,$context,$inherit,$entity) {
		// Sanitize $context
		if (empty($context)) {
			$router = Load::Router();
			$context = $router->getRoute();
		} elseif (is_string($context)) {
			$ro = Load::Router();
			$cpath = $ro->resolvePath($context,FALSE);
			$router = Load::Router($cpath);
			$context = $router->getRoute();
		} elseif (!is_a($context,'Model_route') && !is_a($context,'Model_route_dynamic')) {
			return false;
		}
		if (empty($context->id)) { return false; }
		// Sanitize entity (defaults to $USER)
		if (is_null($entity)) { if (!$entity = Load::USER()) { return false; } }
		if (!$entity = $this->sanitizeModel($entity)) { return false; }

		if (is_array($action) && sizeof($action)==1) {
			$action = reset($action);
		}

		// Begin organizing entities by generation, type, and ids
		$entities = array(
			array($entity[0],array($entity[1]))
			// Ex. array('user',array(123));
		);

		// Get all generations of usergroup entities
		$gm = Load::Model('usergroup');
		$groups = $gm->getGroups($entity,-1);
		$group_ids = array();
		foreach($groups as $g) {
			$entities[] = array('usergroup',$g);
			$group_ids = array_merge($group_ids,$g);
		}

		// Add generation for anonymous entity 
		$entities[] = array($entity[0],array(0));

		// Also get generation of contexts
		if ($inherit) {
			$contexts = array();
			$cm = $context;
			while (!empty($cm->id)) {
				$contexts[] = $cm->id;
				$cm = $cm->getParent();
			}
			$CFG = Load::Config();
			if (isset($CFG->root['id'])) { $contexts[] = $CFG->root['id']; }
			$contexts[] = '/';
			$contexts[] = 0;
			$contexts = array_unique($contexts);
		} else {
			$contexts = array($context->id);
		}

		// Build the array notation for $resource, since it's a bit weird
		if ($resource[0]=='all') {
			if (sizeof($resource)==2) {
				$r_notation = array(
					'OR',
					array(
						'resource_type' => 'all',
						'resource_id' => '0',
					),
					array(
						'resource_type' => $resource[1][0],
						'resource_id' => $resource[1][1],
					),
				);
			} else {
				$r_notation = array(
					'resource_type' => 'all',
					'resource_id' => '0',
				);
			}
		} else {
			$r_notation = array(
				'resource_type' => $resource[0],
				'resource_id' => $resource[1],
			);
		}

		// Build the array notation for $entity
		$e_notation = array(
			'OR',
			array(
				'entity_type' => $entity[0],
				'entity_id' => array($entity[1],0),
			),
		);
		if (!empty($group_ids)) {
			$e_notation[] = array(
				'entity_type' => 'usergroup',
				'entity_id' => $group_ids,
			);
		};

		// Get ALL relevant ACL rules
		$rm = Load::Model('acl_rule');
		$rules = $rm->find(
			array(
				$r_notation,
				'action' => $action,
				'context' => $contexts,
				array(
					'OR',
					'context' => $context->id,
					'inheritable' => 1,
				),
				$e_notation,
			)
		);

		if (empty($rules)) { return false; }

		foreach($contexts as $c) {
			foreach($entities as $e) {
				$allow = FALSE;
				$deny = FALSE;
				foreach($rules as $r) {
					if ($r['context']==$c
						&& $r['entity_type']==$e[0]
						&& in_array($r['entity_id'],$e[1])
					) {
						if ($r['rule']) { $allow = TRUE; }
						else { $deny = TRUE; }
					}
				}
				if ($deny) { return FALSE; }
				elseif ($allow) { return TRUE; }
			}
		}
	}
	
	function allow($entity,$resource,$action='',$context=NULL,$inheritable=TRUE) {
		$this->setRule($entity,1,$resource,$action,$context,$inheritable);
	}

	function deny($entity,$resource,$action='',$context=NULL,$inheritable=TRUE) {
		$this->setRule($entity,0,$resource,$action,$context,$inheritable);
	}

	function remove($entity,$resource,$action='',$context=NULL,$inheritable=TRUE) {
		$this->setRule($entity,-1,$resource,$action,$context,$inheritable);
	}
	
	protected function setRule($entity,$rule,$resource,$action='all',$context=NULL,$inheritable=TRUE) {
		// Sanitize entity
		if (!$entity = $this->sanitizeModel($entity)) return false;
		// Sanitize rule
		if (!is_int($rule) || abs($rule)>1) return false;
		// Sanitize $resource
		if (!$resource = $this->sanitizeModel($resource)) return false;
		// Sanitize $action
		if (!is_string($action)) return false;
		// Sanitize $context
		if (is_null($context)) {
			$router = Load::Router();
			$context = $router->getContext();
		} elseif (is_scalar($context)) {
			$router = Load::Router($context);
			$context = $router->getContext();
		} elseif (!is_object($context)) {
			return false;
		}
		if (!$context->id) {
			return false;
		}
		$ds = Load::Datasource('db');
		$conditions = array(
			'resource_type' => $resource[0],'resource_id' => $resource[1],
			'action' => $action, 'context' => $context->id, 'inheritable' => $inherit,
			'entity_type' => $entity[0], 'entity_id' => $entity[1]);
		$options = array('limit' => 0,'select' => 'id','fetch' => 'col');
		$current_rules = $ds->get('acl_rule',$conditions,$options);
		if ($rule < 0 || sizeof($current_rules) > 1) {
			foreach ($current_rules as $r) {
				$ds->delete('acl_rule',$r);	
			}
		}
		if ($rule>=0) {
			$conditions['rule'] = $rule;
			if (sizeof($current_rules)==1) {
				$conditions['id'] = reset($current_rules);
			}
			$ds->set('acl_rule',$conditions);
		}
	}
	
	protected function sanitizeModel($model) {
		if (empty($model)) {
			return false;
		} elseif (is_array($model)) {
			$mtype = $model[0];
			$mid = $model[1];
			// Are $mtype and $mid valid?
			if (empty($mtype) || !(is_string($mtype) || is_array($mtype)) || !is_scalar($mid)) {
				return false;
			}
			// If we're checking multiple types, we can't have a nonzero id
			if (is_array($mtype) && $mid!=0 && (sizeof($mtype)>2 || (sizeof($mtype)==2 && !in_array('all',$mtype)))) {
				return false;
			}
		} elseif (is_object($model)) {
			$mtype = $model->_m();
			$mid = $model->id;
		} elseif (is_string($model)) {
			$mtype = $model;
			$mid = 0;
		} else { return false; }
		$mid = (int)$mid;
		return array($mtype,$mid);
	}
}