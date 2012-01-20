<?php

$FORM->text('title', 'Title:');
$FORM->textarea('description', 'Description:', array(
	'style' => 'width: 95%; height: 18em;',
	'class' => implode(' ',$H('rte_classname')),
));