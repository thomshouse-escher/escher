<?php

class Model_scheduler_job extends Model {
	protected $_schemaFields = array(
		'plugin'        => 'resource',
		'controller'    => 'resource',
		'method'        => 'resource',
		'resource_type' => 'resource',
		'resource_id'   => 'id',
		'data'          => 'array',
		'process_at'    => 'datetime',
		'created_at'    => 'datetime',
		'started_at'    => 'datetime',
		'updated_at'    => 'datetime',
		'ended_at'      => 'datetime',
		'status'        => array('type' => 'resource', 'default' => 'queued'),
		'node'          => 'string',
		'progress'      => 'smallint',
		'total'         => 'smallint',
		'message'       => 'content',
	);

	function save() {
		if (isset($this->status) && $this->status=='processing') {
			$this->updated_at = time();
		}
		parent::save();
	}
}