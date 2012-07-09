<?php

$FORM->useInputStatus();
$FORM->openFieldset();
$FORM->openInputs(NULL,'Title:');
$E('<span class="input-xxlarge uneditable-input">'.$title.'</span>');
$FORM->closeInputs();
$FORM->openInputs(NULL,'Permalink URL:');
$E('<span class="input-xxlarge uneditable-input">'.$U('./').'</span>');
$FORM->closeInputs();
$FORM->closeFieldset();
