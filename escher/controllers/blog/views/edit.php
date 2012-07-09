<?php
$FORM->open(array('method' => 'POST','onsubmit' => $_unload()));
$UI->getContent('route');
$UI->getContent('blog');
$FORM->openActions();
$FORM->submit('Save');
$FORM->button('Cancel',array(
	'onclick' => $_unload()."window.location='$current_path';"
));
$FORM->close();