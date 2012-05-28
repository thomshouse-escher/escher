<?php

$FORM->useInputStatus();
$FORM->openFieldset(NULL,array('class'=>'form-vertical'));
$FORM->text('page_title','Title:',array('class'=>'span12'));
$FORM->textarea('body', NULL, array(
	'style' => 'height: 24em;',
	'class' => array_merge(array('span12'),$H('rte_classname')),
));
$FORM->closeFieldset();
