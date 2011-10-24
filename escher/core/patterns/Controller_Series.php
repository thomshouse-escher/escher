<?php

abstract class Controller_Series extends Controller {
	protected $ACLRestrictedActions = array('edit','add_entry','edit_entry','delete_entry');
	protected $allowedModelTypes = array();
	protected $seriesType;
	protected $entryType;
	protected $displayConditions = array();
	protected $sortOrder = array('ctime' => -1);
	protected $pageLimit = 10;
	protected $feedLimit = 25;

	function action_index($args) {
		if (empty($this->id)) { Load::Error('500'); }
		$page = Load::Model($this->seriesType,$this->id);
		$ds = Load::Datasource();
		$conditions = array('series_type'=>$this->seriesType,'series_id'=>$this->id);
		if (!empty($this->displayConditions)) {
			$conditions[] = $this->displayConditions;
		}
		if (!empty($args[0]) && is_int($args[0])) {
			$limit = array($this->pageLimit*($args[0]-1),$this->pageLimit);
		} else {
			$limit = $this->pageLimit;
		}
		$this->data['entries'] = array();
		$entries = $ds->get($this->entryType,$conditions,
			array('limit'=>$limit,'order'=>$this->sortOrder));
		if (empty($entries) && is_array($limit)) {
			Load::Error('404');
		}
		foreach($entries as $e) {
			$this->data['entries'][] = Load::Model($this->entryType,$e['id']);
		}
		$this->data['resource'] = array($this->seriesType,$this->id);
		$this->data['description'] = @$page->description;
		$this->data['title'] = @$page->title;
	}
	
	function action_page($args) {
		if (empty($args[0]) || !is_numeric($args[0]) || intval($args[0])<2) {
			$headers = Load::Headers();
			$headers->redirect($this->path->current.'/');
		}
		$this->calledAction = 'index';
		return $this->action_index(array(intval($args[0])));
	}
	
	function action_edit($args) {
		$page = Load::Model($this->seriesType,@$this->id);
		$input = Load::Input();
		$lockout = Load::Lockout();
		if ($lockout->isLocked($page)) {
			$headers = Load::Headers();
			$headers->addNotification('This page is currently being edited by another user.','error');
			$headers->redirect($this->path->current.'/');
		}
		if (!empty($input->post)) {
			$headers = Load::Headers();
			$page->parseFormData($input->post);
			$page->touch();
			if ($page->save()) {
				$headers->redirect($this->path->current.'/');
			} else {
				$headers->addNotification('Page data could not be saved.','error');
			}
		}
		if (!empty($this->id)) { $lockout->lock($page); }
		$this->data['form'] = $page->display('edit');
	}

	function action_entry($args) {
		if (empty($args[0])) { Load::Error('404'); }
		$entry = Load::Model($this->entryType,$args[0]);
		if (empty($entry->id) || $entry->series_type != $this->seriesType || $entry->series_id != $this->id) {
			Load::Error('404');
		}
		$model = Load::Model($entry->model_type,$entry->model_id);
		$this->data['model'] = $model->display('index');
		$this->data['entry'] = $entry;
		$this->data['resource'] = array($this->seriesType,$this->id);
	}
	
	function action_add_entry($args) {
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
	
	function action_edit_entry($args) {
		$input = Load::Input();
		$entry = Load::Model($this->entryType,@$args[0]);
		if (!empty($entry->series_type) && !empty($entry->series_id) && ($entry->series_type != $this->seriesType || $entry->series_id != $this->id)) {
			Load::Error('403');
		}
		if (!empty($input->post)) {
			$entry->parseFormData($input->post);
			$model = Load::Model($entry->model_type,@$entry->model_id);
			$model->parseFormData($input->post);
			if (!empty($input->post['delete_entry'])) {
				$entry->delete();
				$model->delete();
				$series = Load::Model($this->seriesType,$this->id);
				$series->touch(); $series->save();
				$headers = Load::Headers();
				$headers->addNotification('The entry was successfully deleted.','success');
				$headers->redirect($this->path->current.'/');
			}
			$model->touch(); $model->save();
			$this->parseEntryDataFromModel($entry,$model);
			$entry->series_type = $this->seriesType;
			$entry->series_id = $this->id;
			$entry->touch(); $entry->save();
			$series = Load::Model($this->seriesType,$this->id);
			$series->touch(); $series->save();
			$headers = Load::Headers();
			$headers->redirect($this->path->current.'/entry/'.$entry->id);
		}
		$model = Load::Model($entry->model_type,$entry->model_id);
		$lockout = Load::Lockout();
		if ($lockout->isLocked($entry)) {
			$headers = Load::Headers();
			$headers->addNotification('This entry is currently being edited by another user.','error');
			$headers->redirect($this->path->current.'/entry/'.$entry->id);
		}
		if (!empty($entry->id)) { $lockout->lock($entry); }
		$this->data['entry_form'] = $entry->display('edit');
		$this->data['model_form'] = $model->display('edit');
		$this->data['entry'] = $entry;
		$this->display('edit_entry',$this->data);
	}
	
	abstract protected function parseEntryDataFromModel($entry,$model);
}