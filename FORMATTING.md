# Code Formatting Standards

## PHP Code Tags

Long-form PHP code tags should be used in all cases. Short-form tags including
echo shortcuts should never be used.

	<?php /* code */ ?>

## Line Length and Indentation:

Lines of code should be no longer than 85 characters (75 recommended). Tabs
should be used for indentation of lines. For the purposes of display and
line-length calculation, a tab should be considered equal to four spaces.

## Variables and Types

If a variable assignment is too long to fit on a single line, it may be split
into multiple lines (placing the assignment operator at the beginning of
a new line). Subsequent lines should be indented.

	$myverylongvariablename
		= ($value1 * $value2) - ($value3 * $value4);

Strings should be surrounded by single quotes if there are no variables to
parse, or double quotes if there are variables that require parsing.

	$mystring = 'This is a string.'
	$mystring2 = "The value of my variable is $myvar."

If an array definition is too long to fit on a single line, it may be split
into multiple, indented lines. In the case of an associative array, keys
should be placed at the beginning of new lines. A comma may be placed after
the final element for maintainability.

	$myarray = array(
		$value1,
		$value2,
	);

	$myassoc = array(
		'value1' => $value1,
		'value2' => $value2,
	);

## Control Structures

Control statements (if, for, while, etc.) should be written with one space
before the opening parenthesis. Logical operators (AND, OR, etc.) should be
separated from their joined conditions by spaces. Lines of the body of the
control statement should be indented.

	if ($condition1 && $condition2) {
		// code
		// ...
	}

If the control expression is too long to fit on one line, components should
be separated into newlines (placing logical operators at the beginning of the
lines) and separated, and the closing parenthesis should occur on a new line.

	if (
		$condition1
		&& $condition2
		&& $condition3
		|| $condition4
	} {
		// code
	}

Control structures should always use bracket notation, even where it is optional.
Control structures may occur on a single line if they fit.

	if ($condition1) { /* code */ }
	else { /* code */ }

However, if any in a chain of control structures is too long to fit on a
single line, all controls in the chain should be expanded to multiple lines.

	if ($condition1) {
		// long code
		// ...
	} else {
		// code
	}

As with other control structures, if a ternary operator is too long to fit
on a single line, it may be split into multiple lines (placing the question mark
and colon at the beginning of lines). For purposes of clarity, multiple
conditions should be grouped by parentheses in ternary operations.

	$var = ($condition1 || $condition2)
		? $value1
		: $value2;

## Function Calls

Function calls should be written with no space before the opening parenthesis,
before the first parameter, or after the last parameter.  Spaces should be
included after the commas between multiple parameters.

	myfunction($param1, $param2);

If a function call is too long to fit on a single line, parameters should be
indented, and the closing parenthesis should occur on a new line. Nested functions
and inline arrays should receive their own indentation.

	myfunction(
		$param1,
		myfunction2(
			$param21,
			$param22
		),
		array(
			$param3,
			$param4,
		)
	);

## Function Definitions

Function definitions should be written with no space before the opening parenthesis,
before the first parameter, or after the last parameter.  The opening bracket
should occur on the same line as the closing parenthesis, followed by a space.
The body of the function definition should be indented.

	function myfunction($param1, $param2 = 0) {
		// code
	}

If a function definition is too long to fit on a single line, parameters should
be indented, and the closing parenthesis should occur on a new line.

	function myfunction(
		$param1 = 0,
		$param2 = 1,
		$param3 = 2
	) {
		// code
	}

## Class Definitions

Class definitions should be written with the opening bracket on the same line,
preceded by a space. Properties should be defined before methods. Public properties
and methods should be defined before protected and private properties and methods.

	class MyClass {
		public $property1;
		private $property2;

		public function method1() {
			// code
		}

		private function method2() {
			// code
		}
	}

## Comments

Inline-style comments should be used for comments that are one to two lines long.
Block-style comments should be used for longer comments.

	// Inline comment
	if ($condition1) { continue; }

	/* Block comment
	   This is a longer comment
	   that spans multiple lines. */
	if ($conditional2) { continue; }

Files, classes, and other elements in Escher should be documented with DocBlocks
adhering to PHPDoc standards.  Please see the DOCUMENTATION.md guide for details.