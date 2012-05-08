<style type="text/css">

html { overflow-x: hidden; }

ul#uploads { font-size: 0.85em; margin-left: 0px; }
ul#uploads li { list-style: none; padding: 4px 0px; border-bottom: 1px solid #AAA; clear: both; }
ul#uploads li img { float: left; margin-right: 4px; }
ul#uploads li .filename { height: 20px; line-height: 20px; white-space: nowrap; }
ul#uploads li .details .sizes { text-align: center; }
ul#uploads li .details .sizes table { margin-top: 0.5em; width: 100%; }
ul#uploads li .details .sizes td { text-align: center; padding: 0px 1ex; }
ul#uploads li .details .buttons { margin-top: 0.5em; }
ul#uploads.popup li .details .buttons { text-align: right; }

li.upload-collapsed img { width: 20px; height: 20px; }
li.upload-collapsed.thumb .filename { margin-left: 24px; }
li.upload-collapsed .filesize { display: none; }
li.upload-collapsed .details { display: none; }

li.upload-expanded img { width: 100px; height: 100px; border: 1px solid #DDD; }
li.upload-expanded .filename { font-weight: bold; }
li.upload-expanded .filesize { font-weight: normal; }
li.upload-expanded.thumb .filename, li.upload-expanded.thumb .details { margin-left: 106px; }

</style>
<script type="text/javascript" language="javascript">

function uploadToggle(id) {
	var el = document.getElementById('upload-'+id);
	var classes = el.className.split(' ');
	if (classes[0]=='upload-collapsed') {
		classes[0]='upload-expanded';
	} else {
		classes[0]='upload-collapsed';
	}
	el.className = classes.join(' ');
}

function selectFile(form) {
	var url;
	if (form.url) {
		url = form.url.value;
	}
<?php foreach($selectfuncs as $f) {
	$E("\t$f(url);\n");
} ?>
}

</script>
<form action="<?php $E($current_path.'/?'.($popup?'popup=true&':'').'type='.$type); ?>" method="POST" enctype="multipart/form-data">
<input type="file" name="upload" /><input type="submit" />
</form>
<ul id="uploads"<?php if($popup) { $E(' class="popup"'); } ?>>
<?php foreach($uploads as $u) { ?>

<li class="upload-collapsed <?php $E(empty($u['thumburl'])?'no-thumb':'thumb'); ?>" id="upload-<?php $E($u['upload_id']); ?>">
<?php if(!empty($u['thumburl'])) { ?>
	<img src="<?php $E($u['thumburl']); ?>" onclick="uploadToggle(<?php $E($u['id']); ?>);" />
<?php } ?>
	<div class="filename" onclick="uploadToggle(<?php $E($u['upload_id']); ?>);"><?php $E($u['filename']); ?><span class="filesize">
		 (<?php $E($F($u['filesize'],'filesize',0)); ?>)</span></div>
	<div class="details">
		<?php if($popup) { ?>
		<form onsubmit="return false;">
		<?php } if($popup && !empty($u['sizes'])) { ?>
		<label for="url">Image Size:</label> 
		<select name="url">
		<?php $i=0; foreach($u['sizes'] as $k => $v) { ?>
			<option value="<?php $E($v['url']); ?>"<?php if($i==0) { $E(' checked="checked"'); }
				?>><?php $E("$k ({$v['w']}x{$v['h']})"); ?></option>
		<?php $i++; } ?>
		</select>
		<?php } else { ?>
			<div>Uploaded: <?php $E(date('F j, g:ia',strtotime($u['ctime']))); ?></div>
			<?php if ($popup) { ?><input type="hidden" name="url" value="<?php $E($u['url']); ?>" />
		<?php }} if($popup) { ?>
			<div class="buttons"><input type="button" value="Select" onclick="selectFile(this.form);" /></div>
		</form>
		<?php } ?>
	<div style="clear: both; height: 1px;"></div>
	</div>
</li>
<?php } ?>
</ul>