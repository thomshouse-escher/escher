<?php

abstract class Helper_html extends Helper {
	protected $directOutput = FALSE;
	protected $html5LayoutTags = array('article','aside','footer','header','section');
	protected $openTags = array();
	
	abstract function close($tag);
	abstract function closeTo($tag);
	abstract function open($tag,$attrs=array());
	abstract function tag($tag,$contents=NULL,$attrs=array());

	function directOutput($bool=NULL) {
		if (!is_null($bool)) {
			$this->directOutput = (bool)$bool;
		}
		return $this->directOutput;
	}

	protected function parseSelector($string) {
		$arr = preg_split('/([#.])/',$string,-1,PREG_SPLIT_DELIM_CAPTURE);
		$tag = strtolower(array_shift($arr));
		$id = NULL;
		$classes = array();
		while (!empty($arr)) {
			$char = array_shift($arr);
			if ($char=='#') {
				if (is_null($id)) {
					$id = array_shift($arr);
				}
			} elseif ($char=='.') {
				$classes[] = array_shift($arr);
			}
		}
		return array('tag'=>$tag,'id'=>$id,'class'=>$classes);
	}
	
	protected function renderOpeningTag($tag,$attrs) {
		$result = "<$tag";
		foreach($attrs as $a => $v) {
			if (!empty($v)) {
				if ($a=='class') {
					$result .= ' class="'.implode(' ',$v).'"';
				} else {
					$result .= " $a=\"$v\"";
				}
			}
		}
		$result .= '>';
		return $result;
	}
	
	protected function renderClosingTag($tag) {
		return "</$tag>";	
	}
}