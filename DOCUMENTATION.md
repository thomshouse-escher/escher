# Documentation

Escher uses a set of PHPDoc standards for commenting code.  DocBlock styles
should adhere to the rules of the PHPDoc standard.

Some document generators (such as DocBlox) recognize the use of the MarkDown
syntax for enhanced formatting of generated documentation.  MarkDown syntax
should only be used in the long description section of DocBlocks.

The DocBlock templates provided below should be followed for documenting
the appropriate elements:

## File

	<?php
	/**
	* Short description of the file (required)
	*
	* You may place a longer description for the file here if you wish.
	* This description may be multiple lines or paragraphs long.
	*
	* Separate multiple paragraphs with blank lines
	*
	* @author Original Author <author@example.com>
	* @author Another Author <another@example.com>
	* @package [ Escher | Plugin Name ]
	* @subpackage [ Controllers | Models | Helpers ]
	*/

## Class

	/**
	 * Short description of the class (required)
	 *
	 * You may place a longer description for the class here.
	 * This description may be multiple lines or paragraphs long.
	 *
	 * Separate multiple paragraphs with blank lines
	 *
	 * @author Original Author <author@example.com>
	 * @author Another Author <another@example.com>
	 * @package [ Escher | Plugin Name ]
	 * @subpackage [ Controllers | Models | Helpers ]
	 */
	class MyClass extends EscherClass {

## Function

	/**
	 * Short description of the function (required)
	 *
	 * You may place a longer description for the function here.
	 * This description may be multiple lines or paragraphs long.
	 *
	 * @param type Description of param1
	 * @param type Description of param2
	 * @return type Description of return value
	 */
	function myFunction($param1, $param2=NULL) {
		// function code
	}

## Property

	/**
	 * Short description of class property
	 *
	 * @var type
	 */
	 private $myProperty
