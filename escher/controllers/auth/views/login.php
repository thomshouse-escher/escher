<?php

$HTML->tag('h1','Sign In');

$FORM->open(array('method'=>'POST','class'=>'logregform loginform'));

$FORM->openFieldset();
$FORM->text('username','Username:');
$FORM->password('password','Password:');
$FORM->checkbox('persist','Stay signed in',NULL,NULL,TRUE);

$FORM->openActions();
$FORM->submit('Sign in',array('class'=>'btn primary'));
$FORM->close();