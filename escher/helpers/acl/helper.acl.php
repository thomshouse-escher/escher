<?php

/**
 * Helper_acl.php
 * 
 * ACL (Access Control List) Helper base class
 * @author Thom Stricklin <code@thomshouse.net>
 * @version 1.0
 * @package Escher
 */

/**
 * ACL (Access Control List) Helper base class
 * @package Escher
 */
abstract class Helper_acl extends Helper {
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
	function check($resource=NULL,$action=NULL,$context=NULL,$inherit=TRUE,$entity=NULL) {
		// Set $resource to 'all' if it isn't set
		if (is_null($resource)) {
			$resource = 'all';
		} else if ($resource = $this->sanitizeModel($resource)) {
			// If $resource is valid, add 'all' to the resource type
			$resource[0] = array_merge(array('all'),(array)$resource[0]);
		// If $resource is not valid, return false
		} else return false;
		// Cast action to array and add 'all' 
		$action = (array)$action;
		$action[] = 'all';
		// ...and pass it all along to require (since this is just a kinder, gentler version)
		return $this->req($resource,$action,$context,$inherit,$entity);
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
	function req($resource=NULL,$action=NULL,$context=NULL,$inherit=TRUE,$entity=NULL) {
		// Sanitize $resource
		if ($resource = $this->sanitizeModel($resource)) {
			list($rtype,$rid) = $resource;
		} else return false;
		// Sanitize $action
		$action = (array)$action;
		if (empty($action)) return false;
		// Sanitize $context
		if (is_null($context)) {
			$router = Load::Router();
			$context = $router->getContext();
		} elseif (is_string($context)) {
			$router = Load::Router($context);
			$context = $router->getContext();
		} elseif (!is_object($context)) {
			return false;
		}
		if (!$context->id) {
			return false;
		}
		// Sanitize entity (defaults to $USER)
		if (is_null($entity)) {
			if (!$entity = Load::USER()) {
				return false;
			}
		}
		if ($entity = $this->sanitizeModel($entity)) {
			list($etype,$eid) = $entity;
		} else return false;
	
		// This is where the magic will happen
		return $this->determineAccess($rtype,$rid,$action,$context,$context,$inherit,$etype,$eid);
	}
	
	/**
	 * Low-level permission checking.  Checks recursively.  Deny > Allow.  Entity > Group > All entities.
	 * @param string $rtype
	 * @param integer $rid
	 * @param array $action
	 * @param Model $context
	 * @param Model $orig_context
	 * @param boolean $inherit
	 * @param string $etype
	 * @param integer $eid
	 * @param array $groups
	 * @return boolean
	 */
	protected function determineAccess($rtype,$rid,$action,$context,$orig_context,$inherit,$etype,$eid,$groups=NULL) {
		$ds = Load::Datasource('db');
		$r_cond = array();
		// If $rtype is an array, datasource wil automatically interpret as an IN() statement
		$r_cond['resource_type'] = $rtype;
		// There should be no rules set for "all" type and nonzero id, so do some extra checking
		if ($rid && in_array('all',(array)$rtype)) {
			$r_cond[] = array('OR',
				'resource_id' => 0,
				array('AND','resource_id' => $rid,'resource_type' => array('!=' => 'all'))
			);
		// ...Unless we don't have to
		} else {
			$r_cond['resource_id'] = 0;
		}
		// Set Action and Context conditions
		$a_cond = array('action' => $action);
		$c_cond = array('context' => $context->id);
		// If we have already ascended to parent_contexts, we must look for descending rules
		if ($context->id!=$orig_context->id) {
			$c_cond['descend'] = 1;
		}
		// Assemble the conditions
		$conditions = array($r_cond,$a_cond,$c_cond,'entity_type'=>$etype,'entity_id'=>$eid);
		// Sort by 'rule' ascending, so that blocks override allows
		$options = array('order' => array('rule'=>1),'select' => 'rule');
		$result = $ds->get('acl_rule',$conditions,$options);
		// If we found a rule for this entity, return the rule (as bool)
		if (!empty($result)) {
			 return (bool)$result['rule'];
		}
		// Get the group tree for any groups for this entity
		if (is_null($groups)) {
			$groups = $this->getGroupTree($etype,$eid);
		}
		// If there are groups, check for permissions on the groups
		if (!empty($groups)) {
			$conditions = array($r_cond,$a_cond,$c_cond,'entity_type' => 'acl_group','entity_id' => array_keys($groups));
			// Fetching associatively so that group id is the key
			$options = array('limit' => 0,'order' => array('rule'=>1),'select' => 'entity_id,rule','fetch'=>'assoc');
			$result = $ds->get('acl_rule',$conditions,$options);
			// Child groups trump parents, so if a rule is set for a group, unset any rules for the parents
			foreach($result as $k => $r) {
				foreach($groups[$k] as $parent) {
					if (array_key_exists($parent,$result)) {
						unset($result[$parent]);
					}
				}
			}
			// If there are still any results left, return the first one (as bool)
			if (!empty($result)) {
				return (bool)reset($result);
			}
		}
		// Now check for rules for the entity type itself (zero id)
		$conditions = array($r_cond,$a_cond,$c_cond,'entity_type'=>$etype,'entity_id'=>0);
		$options = array('order' => array('rule'=>1),'select' => 'rule');
		$result = $ds->get('acl_rule',$conditions,$options);
		// If there is a rule for the entity type, return (as bool)
		if (!empty($result)) {
			 return (bool)$result['rule'];
		}
		// If we still haven't matched any rules, ascend the $context if possible and try again
		if ($context = $context->getParent()) {
			return $this->determineAccess($rtype,$rid,$action,$context,$orig_context,$inherit,$etype,$eid,$groups);
		} else {
			return false;
		}
	}
	
	function allow($entity,$resource,$action='',$context=NULL,$descend=TRUE) {
		$this->setRule($entity,1,$resource,$action,$context,$descend);
	}

	function deny($entity,$resource,$action='',$context=NULL,$descend=TRUE) {
		$this->setRule($entity,0,$resource,$action,$context,$descend);
	}

	function remove($entity,$resource,$action='',$context=NULL,$descend=TRUE) {
		$this->setRule($entity,-1,$resource,$action,$context,$descend);
	}
	
	protected function setRule($entity,$rule,$resource,$action='all',$context=NULL,$descend=TRUE) {
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
			'action' => $action, 'context' => $context->id, 'descend' => $descend,
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
	
	protected function getGroupTree($etype,$eid) {
		$ds = Load::Datasource('db');
		//Array of associative arrays, where key is original group id and array is ids of ascendant parents
		$group_trees = array();
		$all_groups = array();
		
		$groups = $ds->get('acl_group_member',
			array('member_type'=>$etype,'member_id'=>$eid),
			array('fetch'=>'col','select'=>'group_id','limit'=>0)
		);
		
		foreach ($groups as $g) {
			// If the group already exists in the tree, continue
			if (array_key_exists($g,$group_trees)) {
				continue;
			}
			$this_tree = array();
			$gp = $ds->get('acl_group',array('id'=>$g),array('fetch'=>'one','select'=>'parent_id'));
			while ($gp != 0) {
				if (in_array($gp,$this_tree)) {
					// If caught chasing own tail...
					break;
				}
				$this_tree[] = $gp;
				if (array_key_exists($gp,$group_trees)) {
					$this_tree = array_merge($this_tree,$group_trees[$gp]);
					break;
				}
				$gp = $ds->get('acl_group',array('id'=>$g),array('fetch'=>'one','select'=>'parent_id'));
			}
			$group_trees[$g] = $this_tree;
			while (!empty($this_tree)) {
				$g = array_shift($this_tree);
				$group_trees[$g] = $this_tree;
				if (@array_key_exists($this_tree[0],$group_trees)) {
					break;
				}
			}
		}
		return $group_trees;
	}
}