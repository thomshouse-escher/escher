<?php

class Controller_admin extends Controller {
	function action_index($args) { return TRUE; }

	function execute($args=NULL) {
		$acl = Load::ACL();
		if (!$acl->req('all','sysadmin')) { Load::Error('404'); }
		return parent::execute($args);
	}
}