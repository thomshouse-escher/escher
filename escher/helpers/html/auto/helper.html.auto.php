<?php

class Helper_html_auto extends Helper_html {
	protected $html;
	
	function __construct($args=NULL) {
		$ua = Load::UserAgent();
		if ($ua->supports('html5')) {
			$this->html = Load::Helper('html','html5');
		} else {
			$this->html = Load::Helper('html','xhtml');
		}
	}
	
	function close($tag) { return $this->html->close($tag); }
	function closeTo($tag) { return $this->html->closeTo($tag); }
	function open($tag,$attrs=array()) { return $this->html->open($tag,$attrs); }
	function tag($tag,$contents=NULL,$attrs=array()) { return $this->html->tag($tag,$contents,$attrs); }

	function directOutput($bool=NULL) {
		return $this->html->directOutput($bool);
	}
}