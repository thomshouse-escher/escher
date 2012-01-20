<?php Load::core('patterns/controller.series.php');

class Controller_blog extends Controller_Series {
	protected $allowedModelTypes = array('article');
	protected $seriesType = 'blog';
	protected $entryType = 'blog_entry';
	protected $previewLimit = 140;
	protected $displayConditions = array('pub_status'=>1);
	
	protected function parseEntryDataFromModel($entry,$model) {
		if (empty($model->id)) { Load::Error('500'); }
		if (empty($entry->model_id)) { $entry->model_id = $model->id; }
		$entry->title = $model->title;
		$entry->preview = @$model->summary;
		if (!preg_match('/\S/',strip_tags($entry->preview))) {
			preg_match_all('#<p>.*</p>#i',$model->body,$matches);
			if (!empty($matches)) {
				$entry->preview = '';
				foreach($matches[0] as $m) {
					if (strlen(strip_tags($entry->preview.' '.$m))>$this->previewLimit) {
						break;
					}
					$entry->preview .= $m;
				}
			}
			if (empty($entry->preview)) {
				$entry->preview = substr(strip_tags($model->body),0,$this->previewLimit);
				if (strlen($entry->preview)==$this->previewLimit) {
					$entry->preview = substr($entry->preview,0,$this->previewLimit-3).'...';
				}
			}
		}
	}
	
	function action_entry($args) {
		$entry = Load::Model($this->entryType,@$args[0]);
		$acl = Load::ACL();
		if (empty($entry->pub_status) && !$acl->check(array($this->seriesType,$this->id),'preview')) {
			Load::Error('404');
		}
		if (!empty($entry->permalink) && !preg_match('#^entry/'.$entry->id.'$#',$entry->permalink)) {
			$headers = Load::Headers();
			$headers->redirect('./'.$entry->permalink.'/');
		}
		parent::action_entry($args);
	}
	
	function execute($args=NULL) {
		if (parent::execute($args)) {
			return true;
		}
		if (is_null($args)) { $args = $this->args; }
		$permalink = implode('/',$args);
		$entry = Load::Model($this->entryType,array('permalink'=>$permalink));
		$acl = Load::ACL();
		if (empty($entry->pub_status) && !$acl->check(array($this->seriesType,$this->id),'preview')) {
			Load::Error('404');
		}
		if (!empty($entry->id)) {
			$this->calledAction = 'entry';
			return parent::action_entry(array($entry->id));
		}
		return false;
	}
	

}