<?php

class Model_article extends Model {
	protected $_schemaFields = array(
		'title'         => 'string',
		'created_at'    => 'datetime',
		'created_from'  => 'resource',
		'created_by'    => 'id',
		'modified_at'   => 'datetime',
		'modified_from' => 'resource',
		'modified_by'   => 'id',
		// Content
		'body'    => 'content',
		'summary' => 'content',
	);
	
	function save() {
		if(!preg_match('/\S/',strip_tags($this->summary))) {
			unset($this->summary);
		}
		parent::save();
	}
}