<?php

class Model_user extends Model {
	protected $_metadata = array(
		'avatar_url','avatar_source', // Avatar metadata
		'agreed_terms', // MD5 hash of agreed TOS
		);
	protected $_cache_keys = array(
		array('username'),
		array('email')
	);

	function getUserAuth() {
		global $CFG;
		if (array_key_exists($this->auth,$CFG['userauth'])) {
			$auth = $CFG['userauth'][$this->auth];
			return Load::Helper('userauth',$auth['type'],$auth);
		}
		$hooks = Load::Hooks();
		$hookauth = $hooks->getUserAuths();
		if (array_key_exists($this->auth,$hookauth)) {
			$auth = $hookauth[$this->auth];
			return Load::Helper('userauth',array($auth[0],$auth[1]),$auth[2]);
		}
		$auth = $CFG['userauth']['default'];
		return Load::Helper('userauth',$auth['type'],$auth);
	}
}