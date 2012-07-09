<?php

class Model_blog_entry extends Model {
	protected $_schemaFields = array(
		'title'          =>'string',
		'series_type'    =>'resource',
		'series_id'      =>'id',
		'model_type'     =>'resource',
		'model_id'       =>'id',
		'permalink'      =>'string',
		'tagline'        =>'string',
		'published'      =>'bool',
		'published_at'   =>'datetime',
		'published_from' =>'resource',
		'published_by'   =>'id',
		'created_at'     =>'datetime',
		'created_from'   =>'resource',
		'created_by'     =>'id',
		'modified_at'    =>'datetime',
		'modified_from'  =>'resource',
		'modified_by'    =>'id',
		// Content
		'preview' => 'content',
	);
		
	function touch($fields=NULL,$model=NULL,$create=FALSE) {
		parent::touch($fields,$model,$create);
		if (empty($this->series_type) || empty($this->series_id)) { return false; }
		$this->processTagline();
		$this->processPermalink();
	}
	
	function parseInput($uniqid=NULL) {
		parent::parseInput($uniqid);
		$input = Load::Input();
		$data = $input->post;
		if (!empty($data['publish_entry']) && (empty($this->published_at) || !preg_match('/[1-9]/',$this->published_at))) {
			$this->touch(array('published_at','published_from','published_by')); $this->published=1;
		}
	}
	
	function processTagline() {
		if (empty($this->tagline)) {
			$this->tagline = $this->title;
		}
		$this->tagline = preg_replace(array('/\W+/','/^[^a-z0-9]+/i','/[^a-z0-9]+$/i'),array('-','',''),strtolower($this->tagline));
		$tagline = $this->tagline;
		$i = 1;
		do {
			if ($i>1) { $tagline = $this->tagline."-$i"; }
			$conditions = array('series_type'=>$this->series_type,'series_id'=>$this->series_id,'tagline'=>$tagline);
			if (!empty($this->blog_entry_id)) { $conditions['blog_entry_id'] = array('!='=>$this->blog_entry_id); }
			$i++;
		} while ($check = $this->find($this->_m(),$conditions));
		$this->tagline = $tagline;
	}
	
	function processPermalink() {
		$series = Load::Model($this->series_type,$this->series_id);
		$format = $series->permalink_format;
		if (empty($format)) { $format = '/entry/[id]/'; }
		if (empty($this->blog_entry_id) && strpos($format,'[id]')!==FALSE) {
			$this->permalink = '';
			return;
		}
		if (@$this->published) {
			$time = strtotime($this->published_at);
		} else {
			$time = strtotime($this->modified_at);
		}
		$permalink = str_replace(array('[yyyy]','[yy]','[mm]','[dd]','[tagline]','[id]'),
			array(date('Y',$time),date('y',$time),date('m',$time),date('d',$time),$this->tagline,@$this->blog_entry_id),$format);
		$this->permalink = preg_replace(array('#^/+#','#/+$#'),array('',''),$permalink);
	}

	function save() {
		if (empty($this->permalink)) {
			parent::save();
			$this->processPermalink();
		}
		return parent::save();
	}
}