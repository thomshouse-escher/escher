<?php Load::core('patterns/model.series.php');

class Model_blog extends Series {
	protected $_schema = array('id'=>'int','title'=>'varchar',
		'mtime'=>'datetime','mtype'=>'varchar','mid'=>'int',
		'permalink_format'=>'varchar');
	protected $_content = array('description');
}