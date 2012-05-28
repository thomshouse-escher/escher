<?php

class Controller_page extends Controller {
	function action_index($args) {
		if (empty($this->id)) { Load::Error('500'); }
		$this->data['resource'] = array('page',$this->id);
		if ($page = Load::Model('page',$this->id)) {
			$this->data['body'] = !empty($page->body) ? $page->body : '';
			$this->data['title'] = $page->page_title;
		}
	}
	
	function manage_edit($args) {
		// Attempt to load the page if we have an id
		if (!empty($this->id)) { $page = Load::Model('page',@$this->id); }
		if (!empty($page)) {
			// If the page exists, let's check/set lockout status
			$lockout = Load::Lockout();
			if ($lockout->isLocked($page)) {
				$this->headers->addNotification('This page is currently being edited by another user.','error');
				$this->headers->redirect('./');
			}
			$lockout->lock($page);
		} else {
			// Otherwise, let's load a new page and set the id
			$page = Load::Model('page');
			if (!empty($this->id)) { $page->page_id = $this->id; }
		}

		// Process form data
		if (!empty($this->input->post)) {
			// If discarding draft, empty, save, and redirect to edit
			if (!empty($this->input->post['discard_draft'])) {
				unset($page->draft);
				$page->save();
				$this->headers->addNotification('Saved draft for this page has been discarded.');
				$this->headers->redirect('./edit/');
			}

			if (!empty($this->input->post['save_draft'])) {
				// If we're saving a draft, clone the page model, parse, and serialize
				$draft = clone($page);
				$draft->parseInput();
				$page->draft = serialize($draft);
			} else {
				// Otherwise, empty draft data, parse input
				unset($page->draft);
				$page->parseInput();
				$page->touch();
			}

			// Attempt to save the page and redirect
			if ($page->save()) {
				$this->headers->redirect('./');
			} else {
				$this->headers->addNotification('Page data could not be saved.','error');
			}
		}

		// Special logic for editing a draft
		if (!empty($page->draft)) {
			$page = unserialize($page->draft);
			$this->data['draft'] = TRUE;
		}

		// Pass the model form
		$this->data['form'] = $page->display('edit');
	}
}
