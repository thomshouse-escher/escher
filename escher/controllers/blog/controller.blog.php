<?php

class Controller_blog extends Controller {
	protected $allowedModelTypes = array('article');
	protected $modelType = 'blog';
	protected $entryType = 'blog_entry';
	protected $previewLimit = 140;
	protected $displayConditions = array('published'=>1);
	protected $sortOrder = array('created_at' => -1);
	protected $pageLimit = 10;
	protected $feedLimit = 25;
	
	protected function parseEntryDataFromModel($entry,$model) {
		if (!$model->id()) { Load::Error('500'); }
		if (empty($entry->model_id)) { $entry->model_id = $model->id(); }
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
	
	function action_index($args) {
		$page = $this->model;
		$conditions = array('series_type'=>$this->modelType,'series_id'=>$this->id);
		if (!empty($this->displayConditions)) {
			$conditions[] = $this->displayConditions;
		}
		if (!empty($args[0]) && is_int($args[0])) {
			$limit = array($this->pageLimit*($args[0]-1),$this->pageLimit);
		} else {
			$limit = $this->pageLimit;
		}

		$this->data['entries'] = array();
		$model = Load::Model($this->entryType);
		$entries = $model->find($conditions,
			array('limit'=>$limit,'order'=>$this->sortOrder));
		if (empty($entries) && is_array($limit) && reset($limit)!=0) {
			Load::Error('404');
		}
		if (is_array($entries)) {
			foreach($entries as $e) {
				$this->data['entries'][] = Load::Model($this->entryType,$e['model_id']);
			}
		}
		$this->data['resource'] = array($this->modelType,$this->id);
		$this->data['description'] = @$page->blog_description;
		$this->data['title'] = @$page->blog_title;
	}
	
	function action_page($args) {
		if (empty($args[0]) || !is_numeric($args[0]) || intval($args[0])<2) {
			$headers = Load::Headers();
			$headers->redirect('./');
		}
		$this->calledAction = 'index';
		return $this->action_index(array(intval($args[0])));
	}
	
	function manage_edit($args) {
		$page = $this->model;
		$lockout = Load::Lockout();
		if ($lockout->isLocked($page)) {
			$headers = Load::Headers();
			$headers->addNotification('This page is currently being edited by another user.','error');
			$headers->redirect('./');
		}
		$route = $this->router->getRoute();
		if (!empty($this->input->post)) {
			$route->parseInput();
			$route->save();
			$page->parseInput();
			$page->blog_title = $route->title;
			$page->touch();
			if ($page->save()) {
				$this->headers->redirect('./');
			} else {
				$this->headers->addNotification('Page data could not be saved.','error');
			}
		}
		if (!empty($this->id)) { $lockout->lock($page); }
		$this->UI->setContent('route',$route->display('edit'));
		$this->UI->setContent('blog',$page->display('edit'));
		return TRUE;
	}
	
	function action_entry($args) {
		$entry = Load::Model($this->entryType,@$args[0]);
		$acl = Load::ACL();
		if (empty($entry->published) && !$acl->check($this->model,'preview')) {
			Load::Error('404');
		}
		if (!empty($entry->permalink) && !preg_match('#^entry/'.$entry->id().'$#',$entry->permalink)) {
			$headers = Load::Headers();
			$headers->redirect('./'.$entry->permalink.'/');
		}
		$model = Load::Model($entry->model_type,$entry->model_id);
		$this->data['model'] = $model->display('index');
		$this->data['entry'] = $entry;
		$this->data['resource'] = array($this->modelType,$this->id);
		$this->data['page'] = $this->model->getValues();
	}
	
	function manage_add_entry($args) {
		if (!isset($args[0]) || !in_array($args[0],$this->allowedModelTypes)) {
			if (sizeof($this->allowedModelTypes)==1) {
				$args[0] = $this->allowedModelTypes[0];
			} else {
				$this->display('add_choices',array('types'=>$this->allowedModelTypes));
			}
		}
		$entry = Load::Model($this->entryType);
		$entry->model_type = $args[0];
		$model = Load::Model($args[0]);
		$this->data['entry_form'] = $entry->display('edit');
		$this->data['model_form'] = $model->display('edit');
		$this->data['entry'] = $entry;
		$this->display('edit_entry',$this->data);
	}
	
	function manage_edit_entry($args) {
		$entry = Load::Model($this->entryType,@$args[0]);
		if (!empty($entry->series_type) && !empty($entry->series_id) && ($entry->series_type != $this->modelType || $entry->series_id != $this->id)) {
			Load::Error('403');
		}
		if (!empty($this->input->post)) {
			$entry->parseInput();
			$model = Load::Model($entry->model_type,@$entry->model_id);
			$model->parseInput();
			if (!empty($this->input->post['delete_entry'])) {
				$entry->delete();
				$model->delete();
				$series = $this->model;
				$series->touch(); $series->save();
				$headers = Load::Headers();
				$headers->addNotification('The entry was successfully deleted.','success');
				$headers->redirect('./');
			}
			$model->touch(); $model->save();
			$this->parseEntryDataFromModel($entry,$model);
			$entry->series_type = $this->modelType;
			$entry->series_id = $this->id;
			$entry->touch(); $entry->save();
			$series = $this->model;
			$series->touch(); $series->save();
			$headers = Load::Headers();
			$headers->redirect('./entry/'.$entry->id());
		}
		$model = Load::Model($entry->model_type,$entry->model_id);
		$lockout = Load::Lockout();
		if ($lockout->isLocked($entry)) {
			$headers = Load::Headers();
			$headers->addNotification('This entry is currently being edited by another user.','error');
			$headers->redirect('./entry/'.$entry->id);
		}
		if ($entry->id()) { $lockout->lock($entry); }
		$this->data['entry_form'] = $entry->display('edit');
		$this->data['model_form'] = $model->display('edit');
		$this->data['entry'] = $entry;
		$this->display('edit_entry',$this->data);
	}
	
	function execute($args=NULL) {
		if (parent::execute($args)) {
			return true;
		}
		if (is_null($args)) { $args = $this->args; }
		$permalink = implode('/',$args);
		$entry = Load::Model($this->entryType,array('permalink'=>$permalink));
		$acl = Load::ACL();
		if (empty($entry->published) && !$acl->check($this->model,'preview')) {
			Load::Error('404');
		}
		if (!empty($entry->id)) {
			$this->calledAction = 'entry';
			return parent::action_entry(array($entry->id()));
		}
		return false;
	}
	

}