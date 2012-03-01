<?php
	$FORM->open(array(
		'method' => 'POST',
		'action' => isset($entry['model_id'])
			? $current_path.'/edit_entry/'.$entry['model_id'].'/' 
			: $current_path.'/edit_entry/',
		'onsubmit' => $_unload(),
	));
	$FORM->openFieldset();
	$E($F($model_form,'decode'));
	$E($F($entry_form,'decode'));
	$FORM->openActions();
	if (!empty($entry['published'])) {
		$FORM->submit('',array('value' => 'Save'));
	} else {
		$FORM->submit('Save and Publish',array('name' => 'publish_entry'));
		$FORM->submit('Save Draft');
	}
	if (isset($entry['model_id'])) {
		$FORM->submit('Delete',array(
			'name'=>'delete_entry',
			'onclick' => "return confirm('Are you sure you want to delete this entry?  This action cannot be undone.');",
		));
	}
	$FORM->button('Cancel',array('onclick' => isset($entry['model_id'])
		? $_unload()."window.location='".$current_path.'/entry/'.$entry['model_id']."';"
		: $_unload()."window.location='".$current_path."';",
	));
	$FORM->close();