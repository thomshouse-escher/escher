<!-- Blog title -->
<h1 class="content-title blog-title"><?php $E($title,'Untitled Blog'); ?></h1>

<!-- Blog description -->
<div class="content-description blog-description"><?php $F($description,'decode'); ?></div>

<?php if($_check($resource,array('edit','add_entry'))) { ?>
<!-- Blog actions -->
<div class="content-actions blog-actions">
<?php if($_check($resource,'edit')) { ?>
<a href="<?php $E($current_path.'/edit/'); ?>"><?php $L('Edit'); ?></a> 
<?php } if($_check($resource,'add_entry')) { ?>
<a href="<?php $E($current_path.'/add_entry/'); ?>"><?php $L('Add Entry'); ?></a> 
<?php } ?>
</div>
<?php } ?>

<!-- Blog entries -->
<div class="content-entries blog-entries">
<?php foreach($entries as $e) { ?>
<div class="content-entry blog-entry">
	<!-- Entry title -->
	<h2 class="content-entry-title blog-entry-title"><a href="<?php if(!empty($e['permalink'])) { $E($current_path.'/'.$e['permalink'].'/'); }
		else { $E($current_path.'/entry/'.$e['id']).'/'; }?>"><?php $E($e['title']); ?></a></h2>
	<!-- Entry preview -->
	<div class="content-entry-preview blog-entry-preview"><?php $F($e['preview'],'decode'); ?></div>
</div>
<?php } ?>
</div>