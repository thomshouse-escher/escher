<?php
	$FORM->open(array(
		'method' => 'POST',
		'action' => isset($entry['id'])
			? $current_path.'/edit_entry/'.$entry['id'].'/' 
			: $current_path.'/edit_entry/',
		'onsubmit' => $_unload(),
	));
	$FORM->openFieldset();
	$F($model_form,'decode');
	$F($entry_form,'decode');
	$FORM->openActions();
	if (!empty($entry['pub_status'])) {
		$FORM->submit('',array('value' => 'Save'));
	} else {
		$FORM->submit('Save and Publish',array('name' => 'publish_entry'));
		$FORM->submit('Save Draft');
	}
	if (isset($entry['id'])) {
		$FORM->submit('Delete',array(
			'name'=>'delete_entry',
			'onclick' => "return confirm('Are you sure you want to delete this entry?  This action cannot be undone.');",
		));
	}
	$FORM->button('Cancel',array('onclick' => isset($entry['id'])
		? $_unload()."window.location='".$current_path.'/entry/'.$entry['id']."';"
		: $_unload()."window.location='".$current_path."';",
	));
	$FORM->close();