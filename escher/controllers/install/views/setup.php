<div class="page-header">
	<h2>Site Options</h2>
	<p>The Escher install wizard is nearly complete.  Escher just needs a few pieces of information about the site, the homepage, and any additional pages you wish to create.  You will also need to set up your administrative account.</p>
</div>

<style type="text/css">

#page-fields { display: none; }

</style>

<?php $FORM->open(array('method' => 'POST')); ?>

<div class="row">
	<div class="span10 offset1"><?php
$FORM->setData($values);
$FORM->useInputStatus(TRUE);
$FORM->openFieldset();
$FORM->text('config[title]','Site Title:');
$FORM->text('config[subtitle]','Subtitle:',array('class'=>'input-xlarge'));
$FORM->text('config[wwwroot]','Address:',array('class'=>'input-xlarge'),'Site Root URL');
$FORM->radios('root',array(
	array('page','Single Page',array('checked'=>'checked')),
	array('blog','Blog'),
),'Homepage:',array('class'=>'inline'));
$FORM->closeFieldset();

$E('<div id="page-fields" />');
$FORM->openFieldset('Page 0 Options');
$FORM->text('page[title]','Page Title:');
$FORM->text('page[tag]','Tag:',array('class'=>'input-small url-tag'),"$www/");
$FORM->radios('page[controller]',array(
	array('page','Single Page',array('checked'=>'checked')),
	array('blog','Blog'),
),'Type:',array('class'=>'inline'));
$FORM->closeFieldset();
$E("</div>");

for($i=1; $i<=$pages; $i++) {
	$E("<div id=\"route-$i-fields\" />");
	$FORM->openFieldset("Page $i Options");
	$FORM->text("route[$i][title]",'Page Title:');
	$FORM->text("route[$i][tag]",'Tag:',
		array('class'=>'input-small url-tag'),"$www/".$values["route[$i][tag]"]);
	$FORM->radios("route[$i][controller]",array(
		array('page','Single Page',array('checked'=>'checked')),
		array('blog','Blog'),
	),'Type:',array('class'=>'inline'));
	$FORM->closeFieldset();
	$E("</div>");
}

$FORM->openFieldset();
$FORM->openInputs();
$FORM->button('Add a Page',array('id'=>'add-page'));
$FORM->closeInputs();
$FORM->closeFieldset();

$FORM->openFieldset('Administrative Options');
$FORM->text('admin[username]','Username:');
$FORM->text('admin[display_name]','Display Name:');
$FORM->text('admin[email]','Email Address:');
$FORM->password('admin[password]','Password:',array('autocomplete'=>'off'));
$FORM->password('admin[password2]','Confirm Password:',array('autocomplete'=>'off'));
$FORM->closeFieldset(); ?>
	</div>
</div>

<input type="submit" class="btn btn-primary btn-large pull-right" value="Continue &raquo;" />
<a class="btn btn-large" href="<?php $E("$current_path/config/"); ?>">&laquo; Go Back</a>

<?php

$FORM->close();

$this->headers->loadJQuery();
$this->headers->addFootHTML("
<script type=\"text/javascript\">
	var page = $pages;
	var lastDiv = '#".($pages ? "route-{$pages}-fields" : 'page-fields')."';
	var www = '$www/';

	$(document).ready(function() {
		$('#add-page').click(function() {
			page++;
			var fields = $('#page-fields').clone().attr('id','route-'+page+'-fields');
			$(fields).find('input').each(function() {
				$(this).attr('name',$(this).attr('name').replace(/^page/,'route['+page+']'));
			});
			$(fields).find('legend').text('Page '+page+' Options');
			$(fields).find('.url-tag').keyup(function() {
				$(this).next().text(www+$(this).val());
			});
			$(lastDiv).after(fields);
			$(fields).find('input').first().focus();
			lastDiv = '#route-'+page+'-fields';
		});

		$('.url-tag').keyup(function() {
			$(this).next().text(www+$(this).val());
		});
	});
</script>"); ?>