<?php

class Helper_lockout_default extends Helper_lockout {
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
		$ds = Load::Datasource();
		$ds->set('model_lockout',array('type'=>$rtype,'id'=>$rid,
			'lock_time'=>$ds->time(NOW),'lock_type'=>$etype,'lock_id'=>$eid));
		// Header Check... Unless this is an AJAX request, we need to add a javascript timer
		$input = Load::Input();
		if (!$input->isAjax()) {
			$CFG = Load::Config();
			$headers = Load::Headers();
			$headers->addHeadHTML(
/* Javascript function */'
<script type="text/javascript" language="javascript">
	window.setInterval(function() {
		if (window.XMLHttpRequest) { xhttp=new XMLHttpRequest(); }
		else { xhttp=new ActiveXObject("Microsoft.XMLHTTP"); }
		xhttp.open("GET","'.$CFG['wwwroot'].'/lockout/'.$rtype.'/'.$rid.'/",false);
		xhttp.setRequestHeader("X-Requested-With","XMLHttpRequest");
		xhttp.send(null);
		delete xhttp;
	},'.($this->pollInterval*1000).');
</script>'
/* End Javascript */);

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
		$ds = Load::Datasource();
		if ($ds->get('model_lockout',array(
			'type'=>$rtype,'id'=>$rid,
			'lock_time'=>array('>='=>$ds->time(NOW-$this->lockoutExpiration)),
			array('NOT','lock_type'=>$etype,'lock_id'=>$eid)))) {
				return true;
		} else { return false; }
	}
}