<?php

class Controller_errors extends Controller {
	function execute($args=NULL) {
		// Sanitize $args
		if (is_null($args)) {
			$args = $this->args;
		}
		$args = (array)$args;
		if (isset($this->router->action)) {
			$error = $this->router->action;
		} else {
			$error = array_shift($args);
		}
		if (empty($error)) {
			$error = '404';
		}
		$this->display($error,$args);
	}
}