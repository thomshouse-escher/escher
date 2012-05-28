<div class="page-header">
	<h2>Action Required</h2>
	<p>Escher does not have access to the create the configuration file, <code>config.php</code>.
	You will need to download the file and move it to the root installation path for Escher.</p>
</div>

<p style="text-align: center; padding-bottom: 30px;"><a class="btn btn-large" href="<?php $E("$current_path/config/download/"); ?>">Download config.php</a></p>

<p>Alternatively, you can set the installation path 
<code><?php $E(ESCHER_DOCUMENT_ROOT.'/'); ?></code> to be writable by Escher.

<div style="padding-top: 30px;">
<a class="btn btn-large btn-primary pull-right" href="<?php $E("$current_path/config/install/"); ?>">Continue &raquo;</a>
<a class="btn btn-large" href="<?php $E("$current_path/config/"); ?>">&laquo; Go Back</a>
</div>