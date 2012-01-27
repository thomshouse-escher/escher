<?php

class Model_route_dynamic extends Model {
	protected $_schemaFields = array(
		'parent_id'   => 'id',
		'tag'         => 'string',
		'title'       => 'title',
		'controller'  => 'resource',
		'instance_id' => 'id',
		'subsite'     => 'bool',
		'theme'       => 'resource',
	);
	protected $_schemaKeys = array(
		'parent_tag' => array(
			'type'   => 'unique',
			'fields' => array('parent_id','tag')
		),
	);
}