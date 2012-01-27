<?php Load::core('patterns/model.series.php');

class Model_blog extends Series {
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
	protected $_schemaTriggers = array(
		'touch_modify' => array(
			'blog_modified_at',
			'blog_modified_from',
			'blog_modified_by'
		),
	);
}