<?php
	$FORM->setNameFormat($nameformat);
	$FORM->setData($model);
	$FORM->text('title','Title:', 
		array(
			'style' => 'width: 85%;',
		)
	);
	$FORM->textarea('description',NULL,
		array(
			'style' => 'width: 95%; height: 18em;',
			'class' => implode(' ',$H('rte_classname')),
		)
	);