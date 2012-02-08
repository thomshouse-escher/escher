<h1 class="content-title page-title"><?php $E($title,'Untitled Page'); ?></h1>
<div class="content-body page-body"><?php $E($F($body,'decode')); ?></div>
<?php if($_check($resource,'edit')) { ?>
<div class="content-actions page-actions">
<a href="<?php $E($current_path.'/edit/'); ?>"><?php $L('Edit'); ?></a> 
</div>
<?php } ?>
