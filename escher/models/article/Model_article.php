<?php

class Model_article extends Model {
	protected $_schema = array('id'=>'int','title'=>'varchar',
		'ctime'=>'datetime','ctype'=>'varchar','cid'=>'int',
		'mtime'=>'datetime','mtype'=>'varchar','mid'=>'int');
	protected $_content = array('body','summary');
	
	function save() {
		if(!preg_match('/\S/',strip_tags($this->summary))) {
			unset($this->summary);
		}
		parent::save();
	}
}