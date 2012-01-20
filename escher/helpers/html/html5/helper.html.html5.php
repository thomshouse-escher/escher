<?php

class Helper_html_html5 extends Helper_html {

	function close($tag) {
		$tag = strtolower($tag);
		if (!empty($this->openTags) && $this->openTags[0]['tag']==$tag) {
			array_shift($this->openTags);
		}
		$result .= $this->renderClosingTag($tag);
		if ($this->directOutput) { echo $result; }
		return $result;	
	}

	function closeTo($selector) {
		// Parse the selector
		extract($this->parseSelector($selector));
		$result = '';
		$tagFound = FALSE;
		// Continue closing tags until we've found a match or no tags are left
		while (!empty($this->openTags) && !$tagFound) {
			// Render the next open tag
			$this_tag = array_shift($this->openTags);
			$result .= $this->renderClosingTag($this_tag['tag']);
			// Determine if the current tag matches the selector
			if ($this_tag['tag']==$tag && (is_null($id) || $this_tag['id']==$id) &&
				(empty($class) || sizeof(array_intersect($class,$this_tag['class']))==sizeof($class))) {
					$tagFound = TRUE;
			}
		}
		// Echo/return all closed tags
		if ($this->directOutput) { echo $result; }
		return $result;						
	}

	function open($selector,$attrs=array()) {
		$class = array();
		// Parse the selector
		extract($this->parseSelector($selector));
		// If this is an HTML5 layout tag, define it as a class
		if (in_array($tag,$this->html5LayoutTags)) {
			$class[] = 'html5-'.$tag;
		}
		// Merge selector classes & id into $attrs
		if (isset($attrs['class'])) {
			$attrs['class'] = array_merge((array)$attrs['class'],$class);
		} else { $attrs['class'] = $class; }
		if (!is_null($id)) { $attrs['id'] = $id; }
		if (empty($attrs['class'])) { unset($attrs['class']); }
		// Append to the stack of open tags
		$this_tag = array('tag' => $tag,'id' => NULL,'class' => array());
		if (isset($attrs['id'])) { $this_tag['id'] = $attrs['id']; }
		if (!empty($attrs['class'])) { $this_tag['class'] = $attrs['class']; }
		array_unshift($this->openTags,$this_tag);
		// Render the tag
		$result = $this->renderOpeningTag($tag,$attrs);
		if ($this->directOutput) { echo $result; }
		return $result;				
	}

	function tag($selector,$contents=NULL,$attrs=array()) {
		$class=array();
		// Parse the selector
		extract($this->parseSelector($selector));
		// If this is an HTML5 layout tag, define it as a class
		if (in_array($tag,$this->html5LayoutTags)) {
			$class[] = 'html5-'.$tag;
		}
		// Merge selector classes & id into $attrs
		if (isset($attrs['class'])) {
			$attrs['class'] = array_merge((array)$attrs['class'],$class);
		} else { $attrs['class'] = $class; }
		if (!is_null($id)) { $attrs['id'] = $id; }
		if (empty($attrs['class'])) { unset($attrs['class']); }
		// Render the tag
		if (in_array($tag,$this->selfClosingTags)) {
			$result = $this->renderOpeningTag($tag,$attrs,TRUE);
		} else {
			$result = $this->renderOpeningTag($tag,$attrs).$contents.$this->renderClosingTag($tag);
		}
		if ($this->directOutput) { echo $result; }
		return $result;				
	}
}