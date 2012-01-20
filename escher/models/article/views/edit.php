<?php

$FORM->useInputStatus();
$FORM->text('title','Title:');
$FORM->textarea('body', 'Body:', array(
	'style' => 'width: 95%; height: 24em;',
	'class' => implode(' ',$H('rte_classname')),
));
$FORM->textarea('summary', 'Summary (Optional):', array(
	'style' => 'width: 95%; height: 10em;',
	'class' => implode(' ',$H('rte_classname')),
));