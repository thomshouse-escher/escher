<form method="POST" onsubmit="<?php $E($_unload()); ?>">
	<div><?php $F($form,'decode'); ?></div>
	<p>
		<input type="submit" value="<?php $L('Save'); ?>" />
		<input type="button" value="<?php $L('Cancel'); ?>" onclick="<?php $E($_unload()."window.location='$current_path';"); ?>" />
	</p>
</form>