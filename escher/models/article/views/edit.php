<?php
	$FORM->setNameFormat($nameformat);
	$FORM->setData($model);
	$FORM->useInputStatus();
	$FORM->text('title','Title:',
		array(
			'style' => 'width: 85%',
		)
	);
	$FORM->textarea('body', NULL,
		array(
			'style' => 'width: 95%; height: 24em;',
			'class' => implode(' ',$H('rte_classname')),
		)
	);
	$FORM->textarea('summary', 'Summary (Optional):',
		array(
			'style' => 'width: 95%; height: 10em;',
			'class' => implode(' ',$H('rte_classname')),
		)
	);