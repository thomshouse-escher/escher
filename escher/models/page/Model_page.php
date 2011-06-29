<?php

class Model_page extends Model {
	protected $_schema = array('id'=>'int','title'=>'varchar',
		'ctime'=>'datetime','ctype'=>'varchar','cid'=>'int',
		'mtime'=>'datetime','mtype'=>'varchar','mid'=>'int');
	protected $_content = array('body','draft');
}