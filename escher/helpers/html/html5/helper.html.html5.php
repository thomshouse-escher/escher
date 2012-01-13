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
				(empty($classes) || sizeof(array_intersect($classes,$this_tag['classes'])==sizeof($classes)))) {
					$tagFound = TRUE;
			}
		}
		// Echo/return all closed tags
		if ($this->directOutput) { echo $result; }
		return $result;						
	}

	function open($selector,$attrs=array()) {
		// Parse the selector
		extract($this->parseSelector($selector));
		// If this is an HTML5 layout tag, define it as a class
		if (in_array($tag,$this->html5LayoutTags)) {
			$class[] = 'html5-'.$tag;
		}
		// Merge selector classes & id into $attrs
		if (isset($attrs['class']) && is_array($attrs['class'])) {
			$attrs['class'] = array_merge($attrs['class'],$class);
		} else { $attrs['class'] = $class; }
		if (!is_null($id)) { $attrs['id'] = $id; }
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
		// If content is null, we're really just opening a tag...
		if (is_null($contents)) { return $this->open($selector,$attrs); }
		$classes=array();
		// Parse the selector
		extract($this->parseSelector($selector));
		// If this is an HTML5 layout tag, define it as a class
		if (in_array($tag,$this->html5LayoutTags)) {
			$classes[] = 'html5-'.$tag;
		}
		// Merge selector classes & id into $attrs
		if (isset($attrs['class']) && is_array($attrs['class'])) {
			$attrs['class'] = array_merge($attrs['class'],$classes);
		} else { $attrs['class'] = $classes; }
		if (!is_null($id)) { $attrs['id'] = $id; }
		// Render the tag
		$result = $this->renderOpeningTag($tag,$attrs).$contents.$this->renderClosingTag($tag);
		if ($this->directOutput) { echo $result; }
		return $result;				
	}
}