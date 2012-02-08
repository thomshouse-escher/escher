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
		if (!parent::parseUpload($file)) { return false; }
		$this->touch();
		$this->save();
	}

	function getPath($timestamp=NULL) {
		if (is_null($timestamp)) {
			if (empty($this->ctime)) {
				$this->touch();
			}
			$timestamp = strtotime($this->ctime);
		}
		return date('Y/m',$timestamp);
	}
	
	function getFilename($size='') {
		if (empty($this->filename)) { return false; }
		if (preg_match('#(.+)(?:-(\d+))?\.([a-z0-9]+)$#',strtolower($this->filename),$parts)) {
			list(,$name,$count,$ext) = $parts;
			$name = preg_replace('/[^\w\d-]/','_',$name);
			if (!empty($size)) {
				return $name.(!empty($count) ? "-$count" : '').(is_string($size) ? ".$size" : '').".$ext";
			} elseif (!empty($this->id)) {
				return $this->filename;
			} else {
				$path = $this->getFilePath();
				$count = 0;
				if (file_exists($path)) {
					$dir = scandir($path);
					if (in_array("$name.$ext",$dir)) {
						$count=2;
						while (in_array("$name-$count.$ext",$dir)) {
							$count++;
						}
					}
				}
			}
			$filename = $name;
			if (!empty($count)) { $filename .= "-$count"; }
			$filename .= ".$ext";
			return $this->filename = $filename;
		}		
	}
}