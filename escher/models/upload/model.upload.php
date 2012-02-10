<?php

class Model_upload extends File {
	protected $_schemaFields = array(
		'filename'      => 'string',
		'mimetype'      => 'string',
		'created_at'    => 'datetime',
		'created_from'  => 'resource',
		'created_by'    => 'id',
		'modified_at'   => 'datetime',
		'modified_from' => 'resource',
		'modified_by'   => 'id',
		'filesize'      => array('type' => 'int','unsigned' => TRUE),
		// Content
		'resized_images' => 'array',
	);

	protected $_doImageResizing = TRUE;
	
	function __construct($key=NULL) {
		parent::__construct($key);
		$this->_resizeParameters['thumb'] = array(100,100,TRUE);
	}
	
	function parseUpload($file=array()) {
		// Touch to set created_at
		$this->touch();

		// Clean the filename
		$filename = preg_replace('/[^\w\d_.-]+/','-',strtolower($file['name']));

		// Split the filename and extension
		preg_match('#(.+?)(\.[a-z0-9]*)$#',$filename,$parts);
		list(,$name,$ext) = $parts;

		// Check to see if there are any year/month/name collisions
		$count = 1;
		while ($this->find(
			array(
				'created_at' => array(
					'>=' => date('Y-m-01 00:00:00',strtotime($this->created_at)),
					'<=' => date('Y-m-t 23:59:59',strtotime($this->created_at)),
				),
				'filename' => $filename,
			),
			array('select' => 'upload_id')
		)) {
			// If found, increment the filename and repeat until safe
			$count++;
			$filename = "$name-$count$ext";
		}
		if (!parent::parseUpload($file,$filename)) { return false; }
		$this->save();
	}

	function getFilePath() {
		$CFG = Load::Config();
		return $CFG['document_root'].'/'.$CFG['uploadpath'].'/uploads/'.$this->getPath();
	}

	function getWWWPath() {
		$CFG = Load::Config();
		return $CFG['wwwroot'].'/'.$CFG['uploadpath'].'/uploads/'.$this->getPath();
	}
	
	protected function getPath() {
		if (empty($this->created_at)) { $this->touch(); }
		return date('Y/m',strtotime($this->created_at));
	}
}