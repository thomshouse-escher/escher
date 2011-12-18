<?php

class Entry extends Model {
	protected $_schema = array('id'=>'int','title'=>'varchar',
		'series_type'=>'varchar','series_id'=>'int',
		'model_type'=>'varchar','model_id'=>'int',
		'ctime'=>'datetime','ctype'=>'varchar','cid'=>'int',
		'mtime'=>'datetime','mtype'=>'varchar','mid'=>'int');
}