<?php
	$FORM->open(
		array(
			'method' => 'POST',
			'onsubmit' => $_unload,
		)
	);
	$FORM->openFieldset();
	$F($form,'decode');
	$FORM->openActions();
	$FORM->submit('Save and Publish');
	$FORM->submit('Save Draft',
		array(
			'name' => 'save_draft',
		)
	);
	if (isset($draft)) {
		$FORM->submit('Discard Draft',
			array(
				'name' => 'discard_draft',
			)
		);
	}
	$FORM->button('Cancel',
		array(
			'onclick' => $_unload()."window.location='$current_path';",
		)
	);
	$FORM->closeActions();
	$FORM->closeFieldset();
	$FORM->close();