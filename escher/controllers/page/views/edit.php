<form method="POST" onsubmit="<?php $E($_unload()); ?>">
	<div><?php $F($form,'decode'); ?></div>
	<p>
		<input type="submit" value="<?php $L('Save and Publish'); ?>" />
		<input type="submit" name="save_draft" value="<?php $L('Save Draft'); ?>" />
		<?php if (isset($draft)) { ?><input type="submit" name="discard_draft" value="<?php $L('Discard Draft'); ?>" /><?php } ?>
		<input type="button" value="<?php $L('Cancel'); ?>" onclick="<?php $E($_unload()."window.location='$current_path';"); ?>" />
	</p>
</form>