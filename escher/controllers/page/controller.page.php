<?php

class Controller_page extends Controller {
	
	function action_index($args) {
		if (empty($this->id)) { Load::Error('500'); }
		$page = Load::Model('page',$this->id);
		$this->data['resource'] = array('page',$page->page_id);
		$this->data['body'] = @$page->body;
		$this->data['title'] = @$page->title;
	}
	
	function manage_edit($args) {
		if (!empty($this->id)) { $page = Load::Model('page',@$this->id); }
		if (empty($page)) { $page = Load::Model('page'); }

		$lockout = Load::Lockout();
		if ($lockout->isLocked($page)) {
			$this->headers->addNotification('This page is currently being edited by another user.','error');
			$this->headers->redirect('./');
		}
		if (!empty($this->input->post)) {
			if (!empty($this->input->post['discard_draft'])) {
				unset($page->draft);
				$page->save();
				$this->headers->addNotification('Saved draft for this page has been discarded.');
				$this->headers->redirect('./edit/');
			}
			$page->parseInput();
			unset($page->draft);
			if (!empty($this->input->post['save_draft'])) {
				$draft = clone($page);
				$page->draft = serialize($draft);
			} else {
				$page->touch();
			}
			if ($page->save()) {
				$this->headers->redirect('./');
			} else {
				$this->headers->addNotification('Page data could not be saved.','error');
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