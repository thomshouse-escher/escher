<?php

class Helper_router_dispatch extends Helper_router {
    protected $sourceRouter;
    protected $dispatch;
    protected $base = '';
    protected $args = array();
    protected $defer = FALSE;

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
        if (isset($args['args'])) {
            $this->args = $args['args'];
        }
    }

    function getRoute() { return $this->sourceRouter->getRoute(); }
    function getContext() { return $this->sourceRouter->getRoute(); }

    function getCurrentPath($absolute=TRUE,$args=FALSE,$qsa=FALSE) {
        $path = $this->getParentPath($absolute);
        if (!empty($this->base)) { $path .= '/'.$this->base; }
        if ($args && !empty($this->args)) {
            $path .= '/'.implode('/',$this->args);
            if ($qsa && !empty($_SERVER['QUERY_STRING'])) {
                $current.= '?'.$_SERVER['QUERY_STRING'];
            }
        }
        return $path;
    }

    function getParentPath($absolute=TRUE) {
        $this->defer = TRUE;
        $result = $this->sourceRouter->getCurrentPath($absolute);
        $this->defer = FALSE;
        return $result;
    }

    function getSitePath($absolute=TRUE) {
        $this->defer = TRUE;
        $result = $this->sourceRouter->getSitePath($absolute);
        $this->defer = FALSE;
        return $result;
    }

    function getPathByInstance($controller,$id=NULL,$absolute=TRUE) {
        $this->defer = TRUE;
        $result = $this->sourceRouter->getPathByInstance($controller,$id,$absolute);
        $this->defer = FALSE;
        return $result;
    }

    function getPathById($id,$absolute=TRUE) {
        if (method_exists($this->sourceRouter,'getPathById')) {
            $this->defer = TRUE;
            $result = $this->sourceRouter->getPathById($id,$absolute);
            $this->defer = FALSE;
            return $result;
        } else {
            return false;
        }
    }

    function resolvePath($url,$absolute=TRUE) {
        if (preg_match('#^\.\./\.\.(/.*|)#',$url,$match)) {
            $this->defer = TRUE;
            $result = $this->sourceRouter->getParentPath($absolute).$match[1];
            $this->defer = FALSE;
            return $result;
        } else {
            return parent::resolvePath($url,$absolute);
        }
    }

    function deferringToSource() {
        return $this->defer;
    }
}