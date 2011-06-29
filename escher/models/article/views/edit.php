<div class="content-title article-title"><?php $L('Title:'); ?> <input style="width: 85%;" type="text" id="<?php $E($fieldname_prefix); ?>title" name="<?php $E($fieldname_prefix); ?>title" value="<?php $E(@$title); ?>" /></div>
<div class="content-body article-body"><textarea class="<?php $E(implode(' ',$H('rte_classname'))); ?>" style="width: 95%; height: 24em;" name="<?php $E($fieldname_prefix); ?>body"><?php $E(@$body); ?></textarea></div>
<div class="content-summary article-summary">
<?php $E('Summary (Optional):'); ?><br />
<textarea class="<?php $E(implode(' ',$H('rte_classname'))); ?>" style="width: 95%; height: 10em;" id="<?php $E($fieldname_prefix); ?>summary" name="<?php $E($fieldname_prefix); ?>summary"><?php $E(@$summary); ?></textarea></div>