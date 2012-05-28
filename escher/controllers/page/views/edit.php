<?php
	$FORM->open(array(
		'method' => 'POST',
		'onsubmit' => $_unload(),
	));
	$E($F($form,'decode'));
	$FORM->openFieldset(NULL,array('class'=>'form-vertical'));
	$FORM->openActions();
	$FORM->submit('Save and Publish',array('class' => 'btn-large'));
	$E(' ');
	$FORM->submit('Save Draft',array('name' => 'save_draft','class' => 'btn-large'));
	$E(' ');
	if (isset($draft)) {
		 $FORM->submit('Discard Draft',array('name' => 'discard_draft','class' => 'btn-large'));
		 $E(' ');
	}
	$FORM->button('Cancel',array(
		'onclick' => $_unload()."window.location='$current_path';",
		'class' => 'btn-large',
	));
	$FORM->close();