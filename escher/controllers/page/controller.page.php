<?php

class Controller_page extends Controller {
	protected $modelType = 'page';

	function action_index($args) {
		if (empty($this->id)) { Load::Error('500'); }
		$this->data['resource'] = array('page',$this->id);
		$page = $this->model;
		$this->data['body'] = $page->body;
	}
	
	function manage_edit($args) {
		// Load the page model
		$page = $this->model;
		if (!empty($page->id)) {
			// If the page exists, let's check/set lockout status
			$lockout = Load::Lockout();
			if ($lockout->isLocked($page)) {
				$this->headers->addNotification('This page is currently being edited by another user.','error');
				$this->headers->redirect('./');
			}
			$lockout->lock($page);
		}
		$route = $this->router->getRoute();

		// Process form data
		if (!empty($this->input->post)) {
			// Parse and save route data
			$route->parseInput();
			$route->save();

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
				$draft->page_title = $route->title; // Copy title from route
				$page->draft = serialize($draft);
			} else {
				// Otherwise, empty draft data, parse input
				unset($page->draft);
				$page->parseInput();
				$page->page_title = $route->title; // Copy title from route
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

		// Pass the form views
		$this->UI->setContent('route',$route->display('edit'));
		$this->UI->setContent('page',$page->display('edit'));
		return TRUE;
	}
}
