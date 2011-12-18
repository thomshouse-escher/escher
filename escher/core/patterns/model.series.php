<?php

class Series extends Model {
	protected $_schema = array('id'=>'int','title'=>'varchar',
		'mtime'=>'datetime','mtype'=>'varchar','mid'=>'int');
	protected $_content = array('description');
}