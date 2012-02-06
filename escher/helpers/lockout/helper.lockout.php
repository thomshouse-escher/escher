<?php

class Helper_lockout extends Helper {
	protected $pollInterval = 30;
	protected $lockoutExpiration = 45;
	
	function lock($resource,$entity=NULL) {
		// Get the resource type/id or return false
		if(is_a($resource,'Model') && isset($resource->id)) {
			$rtype = $resource->_m();
			$rid = $resource->id;
		} elseif (is_array($resource) && sizeof($resource)>=2 && !empty($resource[0]) && !empty($resource[1])) {
			$rtype = $resource[0];
			$rid = $resource[1];
		} else { return false; }
		
		// Get the entity type/id or return false
		if (is_null($entity)) {
			$entity = Load::USER();
		}
		if (is_a($entity,'Model')) {
			$etype = $entity->_m();
			$eid = $entity->id;
		} elseif (is_array($entity) && sizeof($entity)>=2 && !empty($entity[0]) && !empty($entity[1])) {
			$etype = $entity[0];
			$eid = $entity[1];
		} else { return false; }
		
		// Lock it!
		$lock = Load::Model('lockout');
		$lock->assignVars(array(
			'resource_type' => $rtype,
			'resource_id'   => $rid,
			'lock_time'     => NOW,
			'locking_type'  => $etype,
			'locking_id'    => $eid
		));
		$lock->save();

		$headers = Load::Headers();

		// Header Check... Unless this is an AJAX request, we need to add a javascript timer
		if (!$headers->isAJAX()) {
			$CFG = Load::Config();
			$headers->addHeadHTML(
				'<script type="text/javascript" language="javascript">'.
				'	window.setInterval(function() {'.
				'		if (window.XMLHttpRequest) { xhttp=new XMLHttpRequest(); }'.
				'		else { xhttp=new ActiveXObject("Microsoft.XMLHTTP"); }'.
				'		xhttp.open("GET","'.$CFG['wwwroot'].'/lockout/'.$rtype.'/'.$rid.'/",false);'.
				'		xhttp.setRequestHeader("X-Requested-With","XMLHttpRequest");'.
				'		xhttp.send(null);'.
				'		delete xhttp;'.
				'	},'.($this->pollInterval*1000).');'.
				'</script>'
			);

		}
	}
	
	function unlock($resource) { }
	function isLocked($resource,$entity=NULL) {
		// Get the resource type/id or return false
		if(is_a($resource,'Model') && isset($resource->id)) {
			$rtype = $resource->_m();
			$rid = $resource->id;
		} elseif (is_array($resource) && sizeof($resource)>=2 && !empty($resource[0]) && !empty($resource[1])) {
			$rtype = $resource[0];
			$rid = $resource[1];
		} else { return false; }
		
		// Get the entity type/id or return false
		if (is_null($entity)) {
			$entity = Load::USER();
		}
		if (is_a($entity,'Model')) {
			$etype = $entity->_m();
			$eid = $entity->id;
		} elseif (is_array($entity) && sizeof($entity)>=2 && !empty($entity[0]) && !empty($entity[1])) {
			$etype = $entity[0];
			$eid = $entity[1];
		} else { $etype = $eid = 0; }
		
		// Check the lock
		$lock = Load::Model('lockout',array(
			'resource_type' => $rtype,
			'resource_id'   => $rid,
			array('NOT',
				'locking_type'  => $etype,
				'locking_id'    => $eid,
			),
		));
		if ($lock && strtotime($lock->lock_time) >= NOW-$this->lockoutExpiration) {
			return true;
		}
		return false;
	}
}