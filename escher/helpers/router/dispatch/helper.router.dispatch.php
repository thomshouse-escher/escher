<?php

class Helper_router_dispatch extends Helper_router {
	protected $sourceRouter;
	protected $dispatch;
	protected $base = '';
	protected $args = array();

	function __construct($args) {
		if (!is_array($args)) {
			throw new ErrorException(
				'Constructor arguments should be in the form of an array');
		}
		if (!isset($args['router']) || !is_a($args['router'],'Helper_router')) {
			throw new ErrorException(
				'Helper_router_dispatch requires a Helper_router object.'
			);
		}
		if (!isset($args['dispatch'])) {
			throw new ErrorException(
				'Helper_router_dispatch requires the name of the dispatch'
			);
		}
		$this->sourceRouter = $args['router'];
		$this->dispatch = $args['dispatch'];
		if (isset($args['base'])) {
			$this->base = $args['base'];
		}
		if (isset($args[3])) {
			$this->args = $args['args'];
		}
	}

	function getCurrentPath($absolute=TRUE, $args=FALSE) {
		$path = $this->getParentPath($absolute).$this->dispatch;
		if ($args && !empty($this->args)) {
			$path .= '/'.implode('/',$this->args);
		}
		return $path.'/';
	}

	function getParentPath($absolute=TRUE) {
		$path = $this->sourceRouter->getCurrentPath($absolute);
		if (!empty($this->pathToDispatch)) {
			$path .= '/'.$this->pathToDispatch;
		}
		return $path.'/';
	}

	function getSitePath($absolute=TRUE) {
		return $this->sourceRouter->getSitePath($absolute);
	}

	function getPathByInstance($controller,$id=NULL,$absolute=TRUE) {
		return $this->sourceRouter->getPathByInstance($controller,$id,$absolute);
	}

	function getPathById($id,$absolute=TRUE) {
		if (method_exists($this->sourceRouter,'getPathById')) {
			return $this->sourceRouter->getPathById($id,$absolute);
		} else {
			return false;
		}
	}

	function resolvePath($url,$absolute=TRUE) {
		if (preg_match('#^\.\./\.\.(/.*|)#',$url,$match)) {
			return $this->sourceRouter->getCurrentPath($absolute).$match[1];
		} else {
			return parent::resolvePath($url,$absolute);
		}
	}
}