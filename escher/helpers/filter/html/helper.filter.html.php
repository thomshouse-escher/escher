<?php

/**
 * Helper_cache_memcache.php
 * 
 * Filter (HTML) Helper class
 * @author Andrew Detwiler <adetwiler@adidamnetworks.com>
 * @version 1.0
 * @package Escher
 */

/**
 * HTML Filter Helper class
 * @package Escher
 */
class Helper_filter_html extends Helper_filter {
	protected $rules = array(
		'allow' => array('div','p','span','img','video'),
		'default' => array(
			'attrs' => array(
				'id' => TRUE,
				'class' => TRUE,
				'style' => TRUE,
			),
			'style' => array(
				'float' => array('left','right','none'),
				'font-weight' => TRUE,
				'color' => TRUE,
			),
			'children' => TRUE,
			'collapse' => FALSE,
		),
		'remove_default' => array(
			'content' => TRUE,
		),
		'tags' => array(
			'head' => array(
				'content' => FALSE,
			),
			'p' => array(
				'attrs' => array(
					'class' => FALSE, // Overrides line 9
				),
				'style' => array(
					'float' => FALSE, // Overrides line 13
					'font-weight' => FALSE, // Overrides line 14
					'text-decoration' => array('line-through'),
				),
				'children' => array('span','b','i','em','strong'),
			),
			'span' => array(
				'style' => array(
					'font-weight' => FALSE,
				),
			),
			'img' => array(
				'attrs' => array(
					'src' => TRUE,
				),
				'collapse' => TRUE,
			),
			'script' => array(
				'content' => FALSE,
			),
		),
	);
	protected $allow = "
		!html,!body,
		@[id|class|style|title|dir<ltr?rtl|lang|xml::lang],
		a[+href|+name|charset|class|hreflang|rel|rev|type|target|title],
		strong~b,em~i,strike,u,p,-ol[type|compact],-ul[type|compact],-li,br,img[longdesc|usemap|
		src|border|alt=|title|hspace|vspace|width|height|align],-sub,-sup,
		-blockquote,-table[border=0|cellspacing|cellpadding|width|frame|rules|
		height|align|summary|bgcolor|background|bordercolor],-tr[rowspan|width|
		height|align|valign|bgcolor|background|bordercolor],tbody,thead,tfoot,
		td[colspan|rowspan|width|height|align|valign|bgcolor|background|bordercolor
		|scope],th[colspan|rowspan|width|height|align|valign|scope],caption,-div,
		-span,-code,-pre,address,-h1,-h2,-h3,-h4,-h5,-h6,hr[size|noshade],-font[face
		|size|color],dd,dl,dt,cite,abbr,acronym,del[datetime|cite],ins[datetime|cite],
		object[classid|width|height|codebase|*],param[name|value|_value],embed[type|width
		|height|src|*],script[src|type],map[name],area[shape|coords|href|alt|target],bdo,
		button,col[align|char|charoff|span|valign|width],colgroup[align|char|charoff|span|
		valign|width],dfn,fieldset,form[action|accept|accept-charset|enctype|method],
		input[accept|alt|checked|disabled|maxlength|name|readonly|size|src|type|value],
		kbd,label[for],legend,noscript,optgroup[label|disabled],option[disabled|label|selected|value],
		q[cite],samp,select[disabled|multiple|name|size],small,
		textarea[cols|rows|disabled|name|readonly],tt,var,big";
	protected $compiledRules = array();

	function html($data) {
		$this->compileHTMLRules();
		$this->dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$this->dom->loadXML($data);
		foreach ($this->dom->childNodes as $node) {
			if (!$this->validateNode($node)) {
				$this->dom->removeChild($node);
			}
		}
		$html = $this->dom->saveXML();
		$html = trim(preg_replace(
			array(
				'/(\s*\n)+/',
				'/^<\?xml[^\n]+\?>\n/i',
			),
			array(
				"\n",
				'',
			),
			$html
		));
		unset($this->dom);
		return $html;
	}

	protected function perform_filter($value,$type) {
		if ($type=='html') {
			return $this->html($value);
		}
	}

	protected function validateNode($node) {
		if (is_a($node,'DOMText')) { return true; }
		if (!is_a($node,'DOMNode')) { return false; }
		if (empty($node->tagName)) { return false; }
		$tagName = strtolower($node->tagName);

		// Check if tag allowed
		$valid = in_array($tagName, $this->compiledRules['allow']);

		// Determine the ruleset to use
		if (array_key_exists($tagName,$this->compiledRules['tags'])) {
			$rules = $this->compiledRules['tags'][$tagName];
		} elseif ($valid) {
			$rules = $this->compiledRules['default'];
		} else {
			$rules = $this->compiledRules['remove_default'];
		}

		// If tag is valid, clean it up
		if ($valid) {
			// Check attributes
			if ($node->hasAttributes()) {
				// Iterate through tha attributes
				$remove = array();
				foreach ($node->attributes as $attrName => $attrNode) {
					$attrName = strtolower($attrName);
					// If attribute is allowed, check it against rules
					if (array_key_exists($attrName,$rules['attrs'])
						&& !empty($rules['attrs'][$attrName])
					) {
						// If this is the style attribute, do style logic
						if ($attrName == 'style') {
							// Parse the styles
							$css = preg_split('/;/',$attrNode->value,-1,PREG_SPLIT_NO_EMPTY);
							$styles = array();
							foreach ($css as $s) {
								$e = explode(':',$s,2);
								$styles[$e[0]] = trim($e[1]);
							}
							// Iterate through each style declaration
							foreach ($styles as $k => $v) {
								// If this style is not allowed, unset it
								if (!array_key_exists($k,$rules['style'])
									|| empty($rules['style'][$k])
										|| (is_array($rules['style'][$k])
										&& !in_array($v,$rules['style'][$k])
								)) {
									unset($styles[$k]);
								}
							}

							// If there are no styles left, remove the attribute
							if (empty($styles)) {
								$remove[] = $attrNode->name;
							// Else write the new styles
							} else {
								$attrNode->value = '';
								foreach ($styles as $k => $v) {
									$attrNode->value .= $k.': '.$v.';';
								}
							}

						// Else if this is the class attribute, do class logic
						} elseif ($attrName == 'class') {
							if (is_array($rules['attrs']['class'])) {
								$attr = array_intersect(
									explode(' ',$attrNode->value),
									$rules['attrs']['class']
								);
								if (empty($attr)) {
									$remove[] = $attrNode->name;
								} else {
									$attrNode->value = implode(' ',$attr);
								}
							}

						// Else do internal logic for other attributes
						} else {
							// Enforce attr values that are restricted to an array
							if (is_array($rules['attrs'][$attrName])
								&& !in_array($attrNode->value,$rules['attrs'][$attrName])
							){
								$remove[] = $attrNode->name;
							}
						}
					// If attribute is not allowed, just remove it
					} else {
						$remove[] = $attrNode->name;
					}
				}
				// Iterate through all of the attributes to remove
				foreach($remove as $attr) {
					$node->removeAttribute($attr);
				}
			}
		}

		if ($node->hasChildNodes()) {
			// If children are allowed, iterate through them
			if ((!$valid && !empty($rules['content'])) // Tag not allowed, but content is
				|| ($valid && empty($rules['collapse'])) // Tag allowed and does not collapse
			){
				$children = array();
				foreach ($node->childNodes as $child) {
					$children[] = $child;
				}
				foreach ($children as $child) {
					if (!$this->validateNode($child)) {
						$node->removeChild($child);
					}
				}
			} else {
				// UNSET childNodes
				if ($node->hasChildNodes()) {
					foreach ($node->childNodes as $child) {
						$node->removeChild($child);
					}
				}
			}
		}

		if (!$valid) {
			if (!empty($rules['content']) && $node->hasChildNodes()) {
				foreach ($node->childNodes as $child) {
					$node->parentNode->appendChild($child->cloneNode(TRUE));
				}
			}
			return false;
		}
		return true;
	}

	protected function compileHTMLRules() {
		if (!empty($this->compiledRules)) { return; }
		$rc = $this->rules;
		foreach($rc['tags'] as $tagname => $tag) {
			if (in_array($tagname,$rc['allow'])) {
				$default = $rc['default'];
			} else {
				$default = $rc['remove_default'];
			}
			foreach($default as $rulename => $rule) {
				if (!array_key_exists($rulename,$tag)) {
					$rc['tags'][$tagname][$rulename] = $rule;
				} elseif (is_array($tag[$rulename]) && is_array($rule)) {
					$rc['tags'][$tagname][$rulename] =
						array_merge($rule,$tag[$rulename]);
				}
			}
		}
		$this->compiledRules = $rc;
	}
}