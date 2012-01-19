<?php

$HTML->tag('h1','Sign In');

$FORM->open(array('method'=>'POST','class'=>'logregform loginform'));
$FORM->setData($post);
$FORM->useInputStatus();

$FORM->openFieldset();
$FORM->text('username','Username:');
$FORM->text('full_name','Full_name:');
$FORM->text('email','Email:');
$FORM->password('password','Password:');

$captcha = $H('captcha_display');
if (sizeof($captcha)>0) {
	$FORM->openFieldset('Captcha');
	$E(implode('',$captcha));
	$FORM->openFieldset();
}

$FORM->checkbox(
	'agree_terms',
	'By checking this box, you acknowledge that you have read and accept the '
		. '<a href="'.$www.'/terms.txt" target="_blank">terms of service</a> of this website.',
	NULL,NULL,TRUE
);

$FORM->openActions();
$FORM->submit('Sign up',array('class'=>'btn large primary'));
$FORM->close();