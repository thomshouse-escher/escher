<?php
/**
 * helper.form.php
 * 
 * Form Helper base class
 * @author Andrew Detwiler <andetwiler@mpsaz.org>
 * @author Thom Stricklin <thom@thomshouse.net>
 * @version 1.0
 * @package Escher
 */

/**
 * Form Helper base class
 * @author Andrew Detwiler <andetwiler@mpsaz.org>
 * @author Thom Stricklin <thom@thomshouse.net>
 * @version 1.0
 * @package Escher
 */
class Helper_form extends Helper {
	protected $fieldsetClass = 'form-horizontal';
	protected $actionsClass = 'form-actions';
	protected $inputsClass = 'control-group';
	protected $inputClass = 'controls';
	protected $blocktextClass = 'help-block';
	protected $sidetextClass = 'help-inline';

	protected $directOutput = FALSE;
	protected $inForm = FALSE;
	protected $inFieldset = FALSE;
	protected $inWrapper = FALSE;
	protected $nameFormat = '';
	protected $data = array();
	protected $inputStatus = array();
	protected $html;

	function __construct($args=NULL) {
		parent::__construct($args);
		$this->html = Load::Helper('html','auto');
	}

	function directOutput($bool=NULL) {
		$result = $this->directOutput;
		if (!is_null($bool)) { $this->directOutput = (bool)$bool; }
		return $result;
	}

	function setData($data) {
		if (!is_array($data)) { return FALSE; }
		$this->data = $data;
		return TRUE;
	}

	function useInputStatus($bool=TRUE) {
		if ($bool) {
			$UI = Load::UI();
			$this->inputStatus = $UI->getInputStatus();
		} else {
			$this->inputStatus = array();
		}
	}

	function setNameFormat($format) {
		if (!is_string($format)) { return FALSE; }
		$this->nameFormat = $format;
		return TRUE;
	}

	function formatName($name) {
		return (strpos($this->nameFormat,'%s'))
			? sprintf($this->nameFormat,$name)
			: $this->nameFormat.$name;
	}

	function open($attrs=array()) {
		$content = '';
		if ($this->inForm) {
			$content = $this->html->closeTo('form');
			$this->inFieldset = FALSE;
			$this->inWrapper = FALSE;
		}
		$this->inForm = TRUE;
		$content .= $this->html->open('form',$attrs);
		return $this->outputResult($content);
	}

	function close() {
		if (!$this->inForm) { return; }
		$this->inForm = FALSE;
		$content = $this->html->closeTo('form');
		return $this->outputResult($content);
	}

	function openFieldset($legand='',$attrs=array()) {
		$content = '';
		if ($this->inFieldset) {
			$content = $this->html->closeTo('fieldset.'.$this->fieldsetClass);
			$this->inWrapper = FALSE;
		}
		$this->inFieldset = TRUE;
		$content .= $this->html->open('fieldset.'.$this->fieldsetClass,$attrs);

		if (!empty($legand)) {
			$content .= $this->html->open('legend');
			$content .= $legand;
			$content .= $this->html->closeTo('legend');
		}

		return $this->outputResult($content);
	}

	function closeFieldset() {
		if (!$this->inFieldset) { return; }
		$this->inFieldset = FALSE;
		$content = $this->html->closeTo('fieldset');
		return $this->outputResult($content);
	}

	function openActions($attrs=array()) {
		$content = '';
		if ($this->inWrapper) {
			$content = $this->html->closeTo('div.'.$this->inWrapper);
		}
		$this->inWrapper = $this->actionsClass;
		$content .= $this->html->open('div.'.$this->actionsClass,$attrs);
		return $this->outputResult($content);
	}

	function closeActions() {
		if (!$this->inWrapper) { return; }
		$content = $this->html->closeTo('div.'.$this->inWrapper);
		$this->inWrapper = FALSE;
		return $this->outputResult($content);
	}

	function openInputs($attrs=array(),$name='') {
		$this->checkInputStatus($name,$attrs,$helptext);
		$content = '';
		if ($this->inWrapper) {
			$content = $this->html->closeTo('div.'.$this->inWrapper);
		}
		$this->inWrapper = $this->inputsClass;
		$content = $this->html->open('div.'.$this->inputsClass,$attrs);
		$content .= $this->html->open('div.'.$this->inputClass);
		return $this->outputResult($content);
	}

	function closeInputs($name='') {
		if (!$this->inWrapper) { return; }
		$content = '';
		if (!empty($name)) {
			$this->checkInputStatus($name,$attrs,$blocktext);
			if (!empty($blocktext)) {
				$content .= $this->_blocktext($blocktext);
			}
		}
		$content .= $this->html->closeTo('div.'.$this->inWrapper);
		$this->inWrapper = FALSE;
		return $this->outputResult($content);
	}

	function text($name,$label='',$attrs=array(),$sidetext='') {
		return $this->inputTag('text',$name,$label,$attrs,$sidetext);
	}

	function password($name,$label='',$attrs=array(),$sidetext='') {
		return $this->inputTag('password',$name,$label,$attrs,$sidetext);
	}

	function textarea($name,$label='',$attrs=array(),$blocktext='') {
		$this->checkInputStatus($name,$attrs,$blocktext);
		$value = array_key_exists($name,$this->data)
			? $this->data[$name]
			: '';
		$attrs['name'] = $this->formatName($name);
		$attrs['type'] = 'textbox';
		$content = $this->html->tag('textarea',$value,$attrs);
		$content .= $this->_blocktext($blocktext);
		return $this->outputResult($this->wrapInput($content,$label,$name));
	}

	function checkbox($name,$label='',$attrs=array(),$helptext='',$inline=TRUE) {
		$this->checkInputStatus($name,$attrs,$helptext);
		$checked_value = isset($attrs['value'])
			? $attrs['value']
			: '1';
		if (array_key_exists($name,$this->data) && $this->data[$name]==$checked_value) {
			$attrs['checked'] = 'checked';
		}
		$attrs['name'] = $this->formatName($name);
		$attrs['type'] = 'hidden';
		$attrs['value'] = $checked_value==='1' ? '0' : '';
		$content = $this->html->open('label.checkbox.inline');
		$content .= $this->html->tag('input',NULL,$attrs);
		$attrs['type'] = 'checkbox';
		$attrs['value'] = $checked_value;
		$content .= $this->html->tag('input',NULL,$attrs);
		if ($inline) {
			$content .= ' '.$label;
			$content .= $this->_blocktext($helptext);
		} else {
			$content .= $this->_sidetext($helptext);
		}
		$content .= $this->html->closeTo('label.checkbox');
		return $this->outputResult($this->wrapInput($content,$inline?'':$label,$name));
	}

	function hidden($name,$attrs=array()) {
		if (array_key_exists($name,$this->data)) {
			$attrs['value'] = $this->data[$name];
		}
		$attrs['name'] = $this->formatName($name);
		$attrs['type'] = 'hidden';
		return $this->outputResult($this->html->tag('input',NULL,$attrs));
	}

	function submit($label='',$attrs=array()) {
		$attrs['type'] = 'submit';
		if (!empty($label)) { $attrs['value'] = $label; }
		return $this->outputResult($this->wrapInput($this->html->tag('input',NULL,$attrs)));
	}

	function reset($label='',$attrs=array()) {
		$attrs['type'] = 'reset';
		if (!empty($label)) { $attrs['value'] = $label; }
		return $this->outputResult($this->wrapInput($this->html->tag('input',NULL,$attrs)));
	}

	function button($label='',$attrs=array()) {
		$attrs['type'] = 'button';
		if (!empty($label)) { $attrs['value'] = $label; }
		return $this->outputResult($this->wrapInput($this->html->tag('input',NULL,$attrs)));
	}

	function select($name,$options=array(),$label='',$attrs=array(),$helptext='') {
		$this->checkInputStatus($name,$attrs,$helptext);
		$attrs['name'] = $this->formatName($name);
		$content = $this->html->tag('select',$this->selectOptions($name,$options),$attrs);
		if (!empty($attrs['multiple'])) {
			$content .= $this->_blocktext($helptext);
		} else {
			$content .= $this->_sidetext($helptext);
		}
		return $this->outputResult($this->wrapInput($content,$label,$name));
	}

	function checkboxes($name,$options=array(),$label='',$attrs=array(),$blocktext='') {
		$this->checkInputStatus($name,$attrs,$blocktext);
		$oname = $this->formatName($name).'[]';
		$content='';
		foreach($options as $o) {
			if (sizeof($o)<2) { continue; }
			if (!isset($o[2])) { $o[2] = array(); }
			$o[2]['name'] = $oname;
			$o[2]['type'] = 'checkbox';
			$o[2]['value'] = $o[0];
			if (array_key_exists($name,$this->data)
				&& ($this->data[$name]==$o[0]
					|| (is_array($this->data[$name])
						&& in_array($o[0],$this->data[$name])
					)
				)
			) {
				$o[2]['checked'] = 'checked';
			}
			$content .= $this->html->open('label.checkbox');
			$content .= $this->html->tag('input','',$o[2]);
			$content .= ' '.$o[1];
			$content .= $this->html->closeTo('label.checkbox');
		}
		$content .= $this->_blocktext($blocktext);
		return $this->outputResult($this->wrapInput($content,$label,$name));
	}

	function radios($name,$options=array(),$label='',$attrs=array(),$blocktext='') {
		$this->checkInputStatus($name,$attrs,$blocktext);
		$oname = $this->formatName($name);
		$content='';
		foreach($options as $o) {
			if (sizeof($o)<2) { continue; }
			if (!isset($o[2])) { $o[2] = array(); }
			$o[2]['name'] = $oname;
			$o[2]['type'] = 'radio';
			$o[2]['value'] = $o[0];
			if (array_key_exists($name,$this->data) && $this->data[$name]==$o[0]) {
				$o[2]['checked'] = 'checked';
			}
			$content .= $this->html->open('label.radio');
			$content .= $this->html->tag('input','',$o[2]);
			$content .= ' '.$o[1];
			$content .= $this->html->closeTo('label.radio');
		}
		$content .= $this->_blocktext($blocktext);
		return $this->outputResult($this->wrapInput($content,$label,$name));
	}

	function sideText($text) {
		return $this->outputResult($this->wrapInput($this->_sidetext($text)));
	}

	function blockText($text) {
		return $this->outputResult($this->wrapInput($this->_blocktext($text)));
	}

	protected function inputTag($type,$name,$label='',$attrs=array(),$sidetext='') {
		$this->checkInputStatus($name,$attrs,$sidetext);
		if (array_key_exists($name,$this->data)) {
			$attrs['value'] = $this->data[$name];
		}

		$attrs['name'] = $this->formatName($name);
		$attrs['type'] = $type;
		$content = $this->html->tag('input',NULL,$attrs);
		$content .= $this->_sidetext($sidetext);
		return $this->outputResult($this->wrapInput($content,$label,$name));
	}

	protected function _sidetext($text='') {
		return $this->html->tag('span',$text,
			array('class'=>$this->sidetextClass)
		);
	}

	protected function _blocktext($text='') {
		return $this->html->tag('p',$text,
			array('class'=>$this->blocktextClass)
		);
	}

	protected function label($content,$attrs='') {
		return $this->html->tag('label',$content,$attrs);
	}

	protected function wrapInput($input,$label='',$name='') {
		if ($this->inWrapper) { return $input; }
		$attrs = array('class'=>array());
		if (!empty($name) && array_key_exists($name,$this->inputStatus)) {
			$attrs['class'][] = $this->inputStatus[$name]['status'];
		}
		$content = $this->html->open('div.'.$this->inputsClass,$attrs);
		if (!empty($label)) {
			$content .= $this->html->tag('label',$label);
		}
		$content .= $this->html->open('div.'.$this->inputClass);
		$content .= $input;
		$content .= $this->html->closeTo('div.'.$this->inputsClass);

		return $content;
	}

	protected function selectOptions($name,$options=array()) {
		if (empty($options) || !is_array($options)) { return ''; }
		$content = '';
		foreach ($options as $g => $o) {
			iF (!is_array($o)) { $o = array($o,$o); }
			if (is_array(reset($o))) {
				$group_attrs = array();
				if (!is_int($g)) { $group_attrs['label'] = $g; }
				$content .= $this->html->tag(
					'optgroup',
					$this->selectOptions($name,$o),
					$group_attrs
				);
			} else {
				if (sizeof($o)<2) { continue; }
				if (!isset($o[2])) { $o[2] = array(); }
				$o[2]['value'] = $o[0];
				if (array_key_exists($name,$this->data)
					&& ($this->data[$name]==$o[0]
						|| (is_array($this->data[$name])
							&& in_array($o[0],$this->data[$name])
						)
					)
				) {
					$o[2]['selected'] = 'selected';
				}
				$content .= $this->html->tag('option',$o[1],$o[2]);
			}
		}
		return $content;
	}

	protected function checkInputStatus($name,&$attrs,&$sidetext) {
		if (!array_key_exists($name,$this->inputStatus)) {
			return array($attrs,$sidetext);
		}
		$status = $this->inputStatus[$name];
		if (isset($attrs['class'])) {
			$attrs['class'] = array_merge((array)$attrs['class'],(array)$status['status']);
		} else {
			$attrs['class'] = array($status['status']);
		}
		if (!empty($status['message'])) {
			$sidetext = $status['message'];
		}
		return array($attrs,$sidetext);
	}

	protected function outputResult($content) {
		if ($this->directOutput) { echo $content; }
		return $content;
	}
}