<?php

$FORM->text('blog_title', 'Title:');
$FORM->textarea('blog_description', 'Description:', array(
	'style' => 'width: 95%; height: 18em;',
	'class' => implode(' ',$H('rte_classname')),
));