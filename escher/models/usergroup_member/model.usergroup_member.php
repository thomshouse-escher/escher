<?php

class Model_usergroup_member extends Model {
	protected $_schemaFields = array(
		'group_id'    => 'id',
		'member_type' => 'resource',
		'member_id'   => 'id',
	);
}