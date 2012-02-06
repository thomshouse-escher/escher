<?php

class Model_lockout extends Model {
	protected $_schemaFields = array(
		'resource_type' => 'resource',
		'resource_id'   => 'id',
		'lock_time'     => 'datetime',
		'locking_type'  => 'resource',
		'locking_id'    => 'id',
	);
	protected $_schemaKeys = array(
		'primary' => array(
			'type' => 'primary',
			'fields' => array('resource_type','resource_id'),
		),
	);
}