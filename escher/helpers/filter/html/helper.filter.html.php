<?php

/**
 * Filter Helper class
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
	protected $definitions = "!html,!body,!blink,!fieldset,!form,!legend,
		@[id|class|style|title|dir<ltr|rtl>|lang],
		a[+href|+name|charset|class|hreflang|rel:nofollow|rev|target|title],abbr[+title],
		acronym[+title],address,area[shape|coords|href|alt|target],bdo,-blockquote,
		/br,caption,cite,-code,col[align|char|charoff|span|valign|width],
		colgroup[align|char|charoff|span|valign|width],dd,del~strike~s[datetime|cite],
		dfn,-div,dl,dt,em~i,-h1,-h2,-h3,-h4,-h5,-h6,/hr,
		/img[+src|border|alt=|title|hspace|vspace|width|height|align],
		ins[datetime|cite],kbd,label[for],-li,map[+name],-ol,p,-pre,q[cite],samp,
		-span,strong~b,-sub,-sup,
		-table[border=0|cellspacing|cellpadding|width|height|bgcolor|background|bordercolor],
		tbody,td[colspan|rowspan|width|height|align|valign|bgcolor|background|bordercolor|scope],
		tfoot,th[colspan|rowspan|width|height|align|valign|scope],thead,
		-tr[rowspan|width|height|align|valign|bgcolor|background|bordercolor],
		tt,u,-ul,var";
	protected $rules = array(
		'allow'  => array(),
		'tags'     => array(),
		'passthru' => array(),
		'aliases'  => array(),
	);

	protected function html($data) {
		$this->compileRules();
		$this->dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$data = preg_replace('/[\n\r]+/',"\n",$data);
		$this->dom->loadHTML($data);
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
				'/<!DOCTYPE[^>]+>/i',
				'#(?<!\s)/>#',
			),
			array(
				"\n",
				'',
				'',
				' />',
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
		return parent::perform_filter($value,$type);
	}

	protected function validateNode($node) {
		if (!is_a($node,'DOMNode')) { return false; }
		if (!is_a($node,'DOMElement')) {
			$valid = TRUE;
			$passthru = FALSE;
		} else {
			if (empty($node->tagName)) { return false; }
			$tagName = strtolower($node->tagName);

			// Check to see if tag should be aliased
			while (array_key_exists($tagName,$this->rules['aliases'])) {
				$tagName = $this->rules['aliases'][$tagName];
			}
			// Replace the node with its alias
			if ($tagName!=strtolower($node->tagName)) {
				$newNode = $this->dom->createElement($tagName);
				if ($node->hasAttributes()) {
					foreach($node->attributes as $attr) {
						$newNode->setAttributeNode($attr);
					}
				}
				if ($node->hasChildNodes()) {
					foreach($node->childNodes as $child) {
						$newNode->appendChild($child);
					}
				}
				$node->parentNode->replaceChild($newNode,$node);
				$node = $newNode;
			}

			// Check if tag allowed
			$valid = in_array($tagName,$this->rules['allow']);
			$passthru = in_array($tagName,$this->rules['passthru']);

			// If tag is valid, clean it up
			if ($valid) {
				$rules = $this->rules['tags'][$tagName];

				// Force attributes
				if (!empty($rules['forced'])) {
					foreach($rules['forced'] as $attr => $value) {
						$node->setAttribute($attr,$value);
					}
				}

				// Add default attributes
				if (!empty($rules['defaults'])) {
					foreach($rules['defaults'] as $attr => $value) {
						if (!$node->hasAttribute($attr)) {
							$node->setAttribute($attr,$value);
						}
					}
				}

				// Check required attributes
				if (!empty($rules['required'])) {
					$success = FALSE;
					foreach($rules['required'] as $attr => $value) {
						if ($node->hasAttribute($attr)) {
							$success = TRUE;
							break;
						}
					}
					if (!$success) {
						$passthru = TRUE;
					}
				}

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
								if ($rules['styles']===TRUE) { continue; }
								// Parse the styles
								$css = preg_split('/\s*;\s*/',trim($attrNode->value),
									-1,PREG_SPLIT_NO_EMPTY);
								$styles = array();
								foreach ($css as $s) {
									$e = preg_split('/:\s*/',$s,2);
									$styles[$e[0]] = trim($e[1]);
								}
								// Iterate through each style declaration
								foreach ($styles as $k => $v) {
									// If this style is not allowed, unset it
									if (!array_key_exists($k,$rules['styles'])
										|| empty($rules['styles'][$k])
											|| (is_array($rules['styles'][$k])
											&& !in_array($v,$rules['styles'][$k])
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
		}

		if ($node->hasChildNodes()) {
			// If children are allowed, iterate through them
			if (($valid && !is_a($node,'DOMElement')) // Node is not HTML
				|| (!$valid && $passthru) // Tag not allowed, but content is
				|| ($valid && empty($rules['selfClosing'])) // Tag allowed and does not collapse
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
		} elseif ($valid && is_a($node,'DOMElement')) {
			if (!$rules['allowEmpty']) { return false; }
			if (!$rules['selfClosing']) {
				$empty = $this->dom->createTextNode('');
				$node->appendChild($empty);
			}
		}

		if (!$valid || $passthru) {
			if ($passthru && $node->hasChildNodes()) {
				foreach ($node->childNodes as $child) {
					$node->parentNode->appendChild($child->cloneNode(TRUE));
				}
			}
			return false;
		}
		return true;
	}

	protected function compileRules() {
		if (!empty($this->rules['allow'])) { return; }
		$rules = $this->rules;

		$masterDefault = $defaultRule = array(
			'attrs'       => array(),
			'forced'      => array(),
			'required'    => array(),
			'defaults'    => array(),
			'styles'      => array(),
			'allowEmpty'  => TRUE,
			'selfClosing' => FALSE,
			'collapse'    => FALSE,
		);

		// Parse the shorthand rules
		$tags = preg_split('/,/',preg_replace('/\s/','',$this->definitions),
			-1,PREG_SPLIT_NO_EMPTY);

		foreach($tags as $t) {
			// Extract the components of each rule
			preg_match(
				'#(!|//?|-|)([^[{~]+)((?:~[^[{]+)*)(\[.*\]|)(\{.*\}|)#',
				$t,
				$match
			);
			$t = array_combine(
				array('mod','tag','alias','attrs','styles'),
				array_slice($match,1)
			);

			// Parse aliases
			$aliases = preg_split('/~/',$t['alias'],-1,PREG_SPLIT_NO_EMPTY);

			// Check for passthru rules
			if ($t['mod']=='!') {
				$rules['passthru'] = array_merge(
					$rules['passthru'],
					array($t['tag']),
					$aliases
				);
				continue;

			// Check for aliases
			} elseif (!empty($aliases)) {
				$rules['aliases'] = array_merge(
					$rules['aliases'],
					array_fill_keys($aliases,$t['tag'])
				);
			}

			// Begin compiling the current rule
			$rule = $t['tag']=='@' ? $masterDefault : $defaultRule;

			// Parse modifiers
			switch ($t['mod']) {
				case '-':  $rule['allowEmpty'] = FALSE; break;
				case '/':  $rule['selfClosing'] = TRUE; break;
			}

			// Parse attributes
			if (!empty($t['attrs'])) {
				$attr_arr = preg_split('/([+|<>=:\[\]])/',$t['attrs'],-1,
					PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
				foreach($attr_arr as $at) {
					switch ($at) {
						case '+': $required = TRUE; break;
						case '<': $choices = array(); break;
						case '>':
							$value = $choices;
							$choices = FALSE;
							break;
						case ':': $forced = TRUE; break;
						case '=':
							$default = TRUE;
							$rule['defaults'][$attr] = '';
							break;
						case '|': case '['; case ']';
							if (isset($choices) && is_array($choices)) { break; }
							if (!empty($attr)) {
								$rule['attrs'][$attr] = $value;
								if (!empty($required)
									&& !array_key_exists($attr,$rule['required'])
								) {
									$rule['required'][$attr] = $value;
								}
							}
							unset($forced,$required,$default,$choices,$capture,$attr);
							$value = TRUE;
							break;
						default:
							if (isset($choices) && is_array($choices)) {
								$choices[] = $at;
							} elseif (!empty($attr)) {
								if (!empty($default)) {
									$rule['defaults'][$attr] = $at;
									$default = FALSE;
								} elseif (!empty($forced)) {
									$rule['forced'][$attr] = $at;
									$forced = FALSE;
								}
							} else {
								$attr = $at;
							}
					}
				}
			}

			// Parse CSS styles
			if (!empty($t['styles'])) {
				$attr_arr = preg_split('/([+|<>=:{}])/',$t['styles'],-1,
					PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
				foreach($attr_arr as $at) {
					switch ($at) {
						case '+': break;
						case '<': $choices = array(); break;
						case '>':
							$value = $choices;
							$choices = FALSE;
							break;
						case ':':
						case '=': $choice = TRUE; break;
						case '|': case '{': case '}':
							if (isset($choices) && is_array($choices)) { break; }
							if (!empty($attr)) {
								$rule['styles'][$attr] = $value;
							}
							unset($choice,$choices,$capture,$attr);
							$value = TRUE;
							break;
						default:
							if (isset($choices) && is_array($choices)) {
								$choices[] = $at;
							} elseif (!empty($attr)) {
								if (!empty($choice)) {
									$value = array($at);
									$choice = FALSE;
								}
							} else {
								$attr = $at;
							}
					}
				}
			}
			if (!empty($rule['styles'])) {
				$rule['attrs']['style'] = TRUE;
			} elseif (array_key_exists('style',$rule['attrs'])
				&& !empty($rule['attrs']['style'])
			) {
				$rule['styles'] = TRUE;
			}

			// Check to see if we are setting new defaults
			if ($t['tag']=='@') {
				$defaultRule = $rule;

			// Merge the current rule with the defaults and save
			} else {
				$rules['allow'][] = $t['tag'];
				$rules['tags'][$t['tag']] = $rule;
			}
		}
		$this->rules = $rules;
	}
}