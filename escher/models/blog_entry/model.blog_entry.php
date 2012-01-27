<?php Load::core('patterns/model.entry.php');

class Model_blog_entry extends Entry {
	protected $_schemaFields = array(
		'id'             =>'int',
		'title'          =>'string',
		'series_type'    =>'resource',
		'series_id'      =>'id',
		'entry_type'     =>'resource',
		'entry_id'       =>'id',
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
		
	function touch($fields=array('c','m'),$model=NULL) {
		parent::touch($fields,$model);
		if (empty($this->series_type) || empty($this->series_id)) { return false; }
		$this->processTagline();
		$this->processPermalink();
	}
	
	function parseFormData($data,$uniqid=NULL) {
		parent::parseFormData($data,$uniqid);
		if (!empty($data['publish_entry']) && (empty($this->pub_time) || !preg_match('/[1-9]/',$this->pub_time))) {
			$this->touch(array('pub_')); $this->pub_status=1;
		}
	}
	
	function processTagline() {
		if (empty($this->tagline)) {
			$this->tagline = $this->title;
		}
		$this->tagline = preg_replace(array('/\W+/','/^[^a-z0-9]+/i','/[^a-z0-9]+$/i'),array('-','',''),strtolower($this->tagline));
		$ds = Load::Datasource();
		$tagline = $this->tagline;
		$i = 1;
		do {
			if ($i>1) { $tagline = $this->tagline."-$i"; }
			$conditions = array('series_type'=>$this->series_type,'series_id'=>$this->series_id,'tagline'=>$tagline);
			if (!empty($this->id)) { $conditions['id'] = array('!='=>$this->id); }
			$i++;
		} while ($check = $ds->get($this->_m(),$conditions));
		$this->tagline = $tagline;
	}
	
	function processPermalink() {
		$series = Load::Model($this->series_type,$this->series_id);
		$format = $series->permalink_format;
		if (empty($format)) { $format = '/entry/[id]/'; }
		if (empty($this->id) && strpos($format,'[id]')!==FALSE) {
			$this->permalink = '';
			return;
		}
		if (@$this->pub_status) {
			$time = strtotime($this->pub_time);
		} else {
			$time = strtotime($this->mtime);
		}
		$permalink = str_replace(array('[yyyy]','[yy]','[mm]','[dd]','[tagline]','[id]'),
			array(date('Y',$time),date('y',$time),date('m',$time),date('d',$time),$this->tagline,@$this->id),$format);
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