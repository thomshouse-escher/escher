<?php

$FORM->useInputStatus();
$FORM->openFieldset(NULL,array('class'=>'form-vertical'));
$FORM->textarea('body', NULL, array(
	'style' => 'height: 24em; width: 100%;',
	'class' => $H('rte_classname'),
));
$FORM->closeFieldset();
