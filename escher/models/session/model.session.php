<?php

class Model_session extends Model {
	protected $_schemaFields = array(
		'session_id'  => array('type' => 'string','length' => 64),
		'created_at'  => 'datetime',
		'modified_at' => 'datetime',
		// Content
		'data' => 'content',
	);
}