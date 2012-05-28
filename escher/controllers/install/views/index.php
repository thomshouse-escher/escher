<div class="page-header">
	<h2>Welcome to Escher</h2>
	<p>Escher is a Web application framework written in PHP based on MVC design patterns. 
	Although its primary purpose is to function as an application development framework,
	Escher provides some basic content management functionality&mdash;such as blogs and
	pages&mdash;out of the box.</p>
</div>

<h2>Before You Begin...</h2>
<p>The Escher install wizard will do its best to detect the best settings for
your server, but it may be helpful to have a basic understanding of your system's
resources and available services.</p>

<div class="row">
	<div class="span9 offset3">
		<h3><b>Required components and settings:</b></h3>
		<ul>
			<li>A database &mdash; MySQL or PostgreSQL</li>
			<li>Database connection settings</li>
		</ul>
		<h3>Optional components and settings:</h3>
		<ul>
			<li>A <a href="#" class="has-popover" title="About Caching" data-content="Escher is highly optimized for caching.<br /><br />A cache service may vastly improve Escher's performance. However, certain hosting environments may not provide a cache service, or may not be well-suited for caching.">cache service</a> &mdash; Memcached or APC</li>
			<?php $headers->addFootHTML('
				<script type="text/javascript">
					$(document).ready(function() {
						$(".has-popover").popover().click(function() { return false; });
					});
				</script>
			'); ?>
			<li>Write access to: <code><?php $E(ESCHER_DOCUMENT_ROOT); ?></code></li>
		</ul>
	</div>
</div>

<div style="padding-top: 30px;">
	<a href="<?php $E("$current_path/config/"); ?>" class="btn btn-primary btn-large pull-right">I'm Ready &raquo;</a>
</div>