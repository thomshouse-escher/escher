<?php

class Model_acl_rule extends Model {
	protected $_schemaFields = array(
		'resource_type' => 'resource',
		'resource_id' => 'id',
		'action' => 'resource',
		'context' => 'string',
		'inheritable' => array('type' => 'bool','default' => TRUE),
		'entity_type' => 'resource',
		'entity_id' => 'id',
		'rule' => array('type' => 'int','range' => 1),
	);
}