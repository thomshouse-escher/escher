<?php

class Model_user extends Model {
	protected $_schemaFields = array(
		'username'     => array('type' => 'string','length' => '32'),
		'user_auth'    => 'resource',
		'password'     => array('type' => 'string'),
		'email'        => 'email',
		'display_name' => array('type' => 'string','length' => '96'),
		// Metadata
		'agreed_terms' => array('metadata' => TRUE,'type' => 'md5'),
		'avatar_url'   => array('metadata' => TRUE,'type' => 'string'),
		'avatar_source' => array('metadata' => TRUE,'type' => 'resource'),
	);
	protected $_schemaKeys = array(
		'username' => array('type'=>'unique','fields'=>'username'),
		'email' => array('type'=>'unique','fields'=>'email'),
	);

	function getUserAuth() {
		return Load::UserAuth($this->auth);
	}
}