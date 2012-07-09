<?php

$FORM->useInputStatus();
$FORM->openFieldset();
$FORM->text('title','Title:',array('class'=>'input-xxlarge'));
$FORM->openInputs(NULL,'Permalink URL:');
$E('<div class="input-prepend"><span class="add-on">'.$U('/').'</span>');
$FORM->text('tag');
$E('</div>');
$FORM->closeInputs();
$FORM->closeFieldset();