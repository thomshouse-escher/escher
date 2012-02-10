<?php

class Model_route_dynamic extends Model {
	protected $_schemaFields = array(
		'parent_id'   => 'id',
		'tag'         => 'resource',
		'title'       => 'string',
		'controller'  => 'resource',
		'instance_id' => 'id',
		'subsite'     => 'bool',
		'theme'       => 'resource',
	);
	protected $_schemaKeys = array(
		'primary'    => array(
			'type' => 'primary',
			'fields' => 'route_id',
		),
		'parent_tag' => array(
			'type'   => 'unique',
			'fields' => array('parent_id','tag')
		),
	);

	function getParent() {
		if (!empty($this->parent_id)) {
			return Load::Model($this,$this->parent_id);
		}
	}
}