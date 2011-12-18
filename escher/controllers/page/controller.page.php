<?php

class Controller_page extends Controller {
	
	function action_index($args) {
		if (empty($this->id)) { Load::Error('500'); }
		$page = Load::Model('page',$this->id);
		$this->data['resource'] = array('page',$page->id);
		$this->data['body'] = @$page->body;
		$this->data['title'] = @$page->title;
	}
	
	function manage_edit($args) {
		$page = Load::Model('page',@$this->id);
		$input = Load::Input();
		$lockout = Load::Lockout();
		if ($lockout->isLocked($page)) {
			$headers = Load::Headers();
			$headers->addNotification('This page is currently being edited by another user.','error');
			$headers->redirect('./');
		}
		if (!empty($input->post)) {
			$headers = Load::Headers();
			if (!empty($input->post['discard_draft'])) {
				unset($page->draft);
				$page->save();
				$headers->addNotification('Saved draft for this page has been discarded.');
				$headers->redirect('./edit/');
			}
			if (!empty($input->post['save_draft'])) {
				$draft = clone($page);
				unset($draft->draft);
				$draft->parseFormData($input->post);
				$page->draft = serialize($draft);
			} else {
				unset($page->draft);
				$page->parseFormData($input->post);
			}
			$page->touch();
			if ($page->save()) {
				$headers->redirect('./');
			} else {
				$headers->addNotification('Page data could not be saved.','error');
			}
		}
		if (!empty($this->id)) { $lockout->lock($page); }
		if (isset($page->draft)) {
			$page = unserialize($page->draft);
			$this->data['draft'] = TRUE;
		}
		$this->data['form'] = $page->display('edit');
	}
}