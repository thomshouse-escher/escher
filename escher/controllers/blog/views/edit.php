<?php
	$FORM->open(array('method' => 'POST','onsubmit' => $_unload()));
	$FORM->openFieldset();
	$E($F($form,'decode'));
	$FORM->openActions();
	$FORM->submit('Save');
	$FORM->button('Cancel',array(
		'onclick' => $_unload()."window.location='$current_path';"
	));
	$FORM->close();