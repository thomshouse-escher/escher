<div class="page-header">
	<h2>Configure Services</h2>
	<p>Please complete the information related to the database and other services
	you wish to use with this Escher installation.  Escher may have detected some
	of these services for you.</p>
</div>

<?php $FORM->open(array('method' => 'POST')); ?>

<div class="row">
	<div class="span10 offset2"><?php
$FORM->setData($values);
$FORM->openFieldset('Database Options');
$FORM->select('db[driver]',array(
	array('mysql','MySQL'),
	array('postgres','PostgreSQL'),
),'Database Type:',array('id'=>'db_driver','class'=>'input-medium')); ?>
<div id="db_options"><?php
$FORM->text('db[host]','Host:',NULL,'Hostname or IP address');
$FORM->text('db[database]','Database Name:');
$FORM->text('db[username]','Username:');
$FORM->text('db[password]','Password:',array('autocomplete'=>'off'));
$FORM->text('db[prefix]','Table Prefix:'); ?>
</div><?php
$FORM->openFieldset('Cache Options');
$FORM->select('cache[type]',array(
	array('none','None'),
	array('memcached','Memcached'),
	array('apc','APC'),
),'Cache Type:',array('id'=>'cache_type','class'=>'input-medium')); ?>
<div id="cache_options"><?php
$FORM->text('cache[host]','Hostname or IP:');
$FORM->text('cache[port]','Port Number:');
$FORM->text('cache[prefix]','Key Prefix:'); ?>
</div><?php
$FORM->closeFieldset(); ?>
	</div>
</div>

<input type="submit" class="btn btn-primary btn-large pull-right" value="Continue &raquo;" />
<a class="btn btn-large" href="<?php $E("$current_path/"); ?>">&laquo; Go Back</a>

<?php

$FORM->close();

$this->headers->loadJQuery();
$this->headers->addFootHTML('
<script type="text/javascript">
	var dbs_detected = '.json_encode($dbs_detected).';
	var caches_detected = '.json_encode($caches_detected).';

	function toggleDB() {
		if ($.inArray($("#db_driver").val(),dbs_detected)>-1) {
			$("#db_driver + span").text("Detected");
			$("#db_driver").parent().parent().addClass("success");
		} else {
			$("#db_driver + span").text("");
			$("#db_driver").parent().parent().removeClass("success");
		}
		if ($("#db_driver").val()!="") {
			$("#db_options").show();
			$("#db_options input").removeAttr("disabled");
		} else {
			$("#db_options").hide();
			$("#db_options input").attr("disabled","disabled");
		}
	}

	function toggleCache() {
		if ($.inArray($("#cache_type").val(),caches_detected)>-1) {
			$("#cache_type + span").text("Detected");
			$("#cache_type").parent().parent().addClass("success");
		} else {
			$("#cache_type + span").text("");
			$("#cache_type").parent().parent().removeClass("success");
		}
		if ($("#cache_type").val()=="memcached") {
			$("#cache_options").show();
			$("#cache_options input").removeAttr("disabled");
		} else {
			$("#cache_options").hide();
			$("#cache_options input").attr("disabled","disabled");
		}
	}

	$(document).ready(function() {
		toggleDB();
		toggleCache();
		$("#db_driver").change(function() { toggleDB(); });
		$("#cache_type").change(function() { toggleCache(); });
	});
</script>');

?>