<?php
/**
 * helper.form.php
 * 
 * Form Helper base class
 * @author Andrew Detwiler <andetwiler@mpsaz.org>
 * @version 1.0
 * @package Escher
 */

/**
 * Form Helper base class
 * @package Escher
 */

class Helper_form extends Helper {
	protected $directOutput = FALSE;
	private $html;
	private $data=array();

	function __construct($args=NULL) {
		$this->html = Load::Helper('html','auto');
		$this->directOutput = TRUE;
	}

	function directOutput($bool=NULL) {
		$result = $this->directOutput;
		if (!is_null($bool)) { $this->directOutput = (bool)$bool; }
		return $result;
	}

	function open($attrs=NULL) {
		$result = $this->html->open('form',$attrs);
		$this->outputResult($result);

		return $result;
	}

	function openFieldset($attrs=NULL) {
		$result = $this->html->open('fieldset',$attrs);
		$this->outputResult($result);

		return $result;
	}

	function openActions($attrs=NULL) {
		$result = $this->openDiv('actions');
		$this->outputResult($result);

		return $result;
	}

	function close() {	
		$result = $this->html->closeTo('form');
		$this->outputResult($result);

		return $result;
	}

	function closeFieldset() {
		$result = $this->html->closeTo('fieldset');
		$this->outputResult($result);

		return $result;
	}

	function closeActions($attrs=NULL) {
		$result = $this->closeDiv();
		$this->outputResult($result);

		return $result;
	}

	function textarea($name,$label=NULL,$attrs=NULL) {
		if (empty($name)) { return false; }
		if (empty($attrs)) { $attrs=array(); }

		$attrs['name'] = $name;
		$attrs['type'] = 'textbox';
		$input = $this->html->open('textarea',$attrs);
		$input .= $this->html->closeTo('textarea');
		$result = $this->wrapInput($input,$label);
		$this->outputResult($result);

		return $result;
	}

	function textbox($name,$label=NULL,$attrs=NULL) {
		if (empty($name)) { return false; }
		if (empty($attrs)) { $attrs=array(); }

		$attrs['name'] = $name;
		$attrs['type'] = 'textbox';
		$input = $this->html->tag('input',NULL,$attrs);
		$result = $this->wrapInput($input,$label);
		$this->outputResult($result);

		return $result;
	}

	function password($name,$label=NULL,$attrs=NULL) {
		if (empty($name)) { return false; }
		if (empty($attrs)) { $attrs=array(); }

		$attrs['name'] = $name;
		$attrs['type'] = 'password';
		$input = $this->html->tag('input',NULL,$attrs);
		$result = $this->wrapInput($input,$label);
		$this->outputResult($result);

		return $result;
	}

	function checkbox($name,$label=NULL,$attrs=NULL) {
		if (empty($name)) { return false; }
		if (empty($attrs)) { $attrs=array(); }

		$attrs['name'] = $name;
		$attrs['type'] = 'checkbox';
		$result = $this->openDiv('inputs');
		$result .= $this->openDiv('input');
		$input = $this->html->tag('input',NULL,$attrs);
		if (!empty($label)) { $input .= $this->html->tag('span',$label); }	
		$result .= $this->label($input);	
		$result .= $this->closeDiv();
		$result .= $this->closeDiv();

		$this->outputResult($result);

		return $result;
	}

	function radio($name,$label=NULL,$attrs=NULL) {
		if (empty($name)) { return false; }
		if (empty($attrs)) { $attrs=array(); }

		$attrs['name'] = $name;
		$attrs['type'] = 'radio';
		$result = $this->openDiv('inputs');
		$result .= $this->openDiv('input');
		$input = $this->html->tag('input',NULL,$attrs);
		if (!empty($label)) { $input .= $this->html->tag('span',$label); }	
		$result .= $this->label($input);	
		$result .= $this->closeDiv();
		$result .= $this->closeDiv();

		$this->outputResult($result);

		return $result;
	}

	function hidden($name,$value,$attrs=NULL) {
		if (empty($name) && !isset($value)) { return false; }
		if (empty($attrs)) { $attrs=array(); }

		$attrs['name'] = $name;
		$attrs['value'] = $value;
		$attrs['type'] = 'hidden';
		$result = $this->html->tag('input',NULL,$attrs);
		$this->outputResult($result);

		return $result;
	}

	function submit($label=NULL,$attrs=NULL) {
		if (empty($attrs)) { $attrs=array(); }

		$attrs['type'] = 'submit';
		if (!empty($label)) { $attrs['value'] = $label; }
		$result = $this->html->tag('input',NULL,$attrs);
		$this->outputResult($result);

		return $result;
	}

	function reset($label=NULL,$attrs=NULL) {
		if (empty($attrs)) { $attrs=array(); }

		$attrs['type'] = 'reset';
		if (!empty($label)) { $attrs['value'] = $label; }
		$result = $this->html->tag('input',NULL,$attrs);
		$this->outputResult($result);

		return $result;
	}

	function button($name,$label=NULL,$attrs=NULL) {
		if (empty($name)) { return false; }
		if (empty($attrs)) { $attrs=array(); }

		if (!empty($name)) { $attrs['name'] = $name; }
		$attrs['type'] = 'button';
		if (!empty($label)) { $attrs['value'] = $label; }
		$result = $this->html->tag('input',NULL,$attrs);
		$this->outputResult($result);

		return $result;
	}

	function select($name,$options=NULL,$label=NULL,$attrs=NULL) {
		if (empty($name)) { return false; }
		if (empty($attrs)) { $attrs=array(); }

		$attrs['name'] = $name;
		
		$content=array();
		if (!empty($options)) {
			foreach ($options as $opt) {
				$opt[2]['value'] = $opt[0];
				$content[] = $this->html->tag('option',$opt[1],$opt[2]);
			}
		}

		$input = $this->html->tag('select',implode('',$content),$attrs);
		$result = $this->wrapInput($input,$label);
		$this->outputResult($result);

		return $result;
	}

	protected function openDiv($class=NULL, $attrs=NULL) {
		if (empty($attrs)) { $attrs=array(); }
		if (!empty($class)) { $attrs['class'][] = $class; }

		$result = $this->html->open('div',$attrs);

		return $result;
	}

	protected function closeDiv() {
		$result = $this->html->closeTo('div');

		return $result;
	}

	protected function label($content,$attrs=NULL) {
		$result = $this->html->tag('label',$content,$attrs);

		return $result;
	}

	protected function wrapInput($input=NULL,$label=NULL) {
		$result = $this->openDiv('inputs');
		if (!empty($label)) { $result .= $this->label($label); }
		$result .= $this->openDiv('input');
		if (!empty($input)) { $result .= $input; }
		$result .= $this->closeDiv();
		$result .= $this->closeDiv();

		return $result;
	}

	protected function outputResult($result) { if ($this->directOutput) echo $result; }
}