<form method="POST" action="<?php if (isset($entry['id'])) {
	$E($current_path.'/edit_entry/'.$entry['id'].'/'); } else {
	$E($current_path.'/edit_entry/'); } ?>" onsubmit="<?php $E($_unload()); ?>">
	<div><?php $F($model_form,'decode'); ?></div>
	<div><?php $F($entry_form,'decode'); ?></div>
	<p>
	<?php if (!empty($entry['pub_status'])) { ?>
		<input type="submit" value="<?php $L('Save'); ?>" />
	<?php } else { ?>
		<input type="submit" name="publish_entry" value="<?php $L('Save and Publish'); ?>" />
		<input type="submit" value="<?php $L('Save Draft'); ?>" />
	<?php } if (isset($entry['id'])) { ?>
		<input type="submit" name="delete_entry" onclick="return confirm('<?php $L('Are you sure you want to delete this entry?  This action cannot be undone.'); ?>');" value="<?php $L('Delete'); ?>" />
	<?php } ?>
		<input type="button" value="<?php $L('Cancel'); ?>" onclick="<?php if (isset($entry['id'])) {
			$E($_unload()."window.location='".$current_path.'/entry/'.$entry['id']."';"); } else {
			$E($_unload()."window.location='".$current_path."';"); } ?>" />
	</p>
</form>
