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
		return Load::UserAuth($this->auth);
	}
}