<?php

class Model_page extends Model {
	protected $_schemaFields = array(
		'page_title'     => 'string',
		'created_at'     =>'datetime',
		'created_from'   =>'resource',
		'created_by'     =>'id',
		'modified_at'    =>'datetime',
		'modified_from'  =>'resource',
		'modified_by'    =>'id',
		// Content
		'body'  => 'content',
		'draft' => array('type' => 'content','null' => TRUE),
	);
}