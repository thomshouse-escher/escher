<?php

$E('<div><a href="'.$U('./').'">');
$E($page['blog_title'],'Blog');
$E(':</a></div>');

?>
<h1><?php $E($entry['title']); ?></h1>
<?php if($_check($resource,'edit')) { ?>
<div class="actions">
<a class="btn btn-mini" href="<?php $E($current_path.'/edit_entry/'.$entry['blog_entry_id'].'/'); ?>"><?php $L('Edit'); ?></a> 
</div>
<?php }

$E($F($model,'decode'));

$E('<div><a href="'.$U('./').'">&larr; Back to ');
$E($page['blog_title'],'Blog');
$E('</a></div>');

?>