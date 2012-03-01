<?php

class Model_blog extends Model {
	protected $_schemaFields = array(
		'blog_title' => 'string',
		'blog_modified_at' => 'datetime',
		'blog_modified_from' => 'resource',
		'blog_modified_by' => 'id',
		// Metadata
		'permalink_format' => array('metadata' => TRUE,'type' => 'string'),
		// Content
		'blog_description' => 'content',
	);
}