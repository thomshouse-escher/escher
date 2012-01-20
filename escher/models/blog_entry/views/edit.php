<?php
	$FORM->setNameFormat($nameformat);
	$FORM->setData($model);
	$FORM->text('tagline','Tagline:');
	$FORM->hidden('model_type',
		array(
			'value' => @$model_type
		)
	);