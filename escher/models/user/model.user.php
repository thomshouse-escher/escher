<?php

class Model_user extends Model {
	protected $_schemaFields = array(
		'username'     => 'resource',
		'user_auth'    => 'resource',
		'password'     => array('type' => 'string'),
		'email'        => 'email',
		'display_name' => array('type' => 'string','length' => '96'),
		// Metadata
		'enctype'       => array('metadata' => TRUE,'type' => 'resource'),
		'agreed_terms'  => array('metadata' => TRUE,'type' => 'md5'),
		'avatar_url'    => array('metadata' => TRUE,'type' => 'string'),
		'avatar_source' => array('metadata' => TRUE,'type' => 'resource'),
	);
	protected $_schemaKeys = array(
		'username' => array('type'=>'unique','fields'=>'username'),
	);

	function register($vars) {
		if (!empty($this->user_id)) { return; }
		$this->assignVars($vars);
		$hooks = Load::Hooks();
		if ($this->save()) {
			if (empty($_SESSION['user_id'])) {
				$_SESSION['user_id'] = $this->user_id;
			}
			$hooks->runEvent('register_success');
			return $this;
		}
		$hooks->runEvent('register_error');
		return FALSE;
	}

	function getUserAuth() {
		if (!empty($this->user_auth)) {
			return Load::UserAuth($this->user_auth);
		} else {
			return Load::UserAuth();
		}
	}
}
