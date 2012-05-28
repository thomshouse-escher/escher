<!DOCTYPE HTML>
<html lang="en" class="<?php echo $useragent_classes; ?>">
<head>
<title><?php $E($this->headers->getTitle()); ?></title>
<?php
$this->headers->addMeta('viewport','width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable = no;');
$this->headers->addMeta('Content-Type','text/html;charset=utf-8');
$this->headers->addLink('stylesheet',"$theme_dir/css/bootstrap.css");
$this->headers->addLink('stylesheet',"$theme_dir/css/bootstrap-responsive.css");
$this->headers->addLink('stylesheet','http://fonts.googleapis.com/css?family=PT+Sans:400');
$this->headers->loadJQuery();
$this->headers->addJS("$theme_dir/js/bootstrap.min.js");
$E($this->headers->getHeaders());
?>
</head>
<body>

<div id="escher-bg"></div>
<div class="container" id="content">
	<div class="row">
		<div id="header" class="span4 offset4">
			<h1>Escher</h1>
			<p>A PHP MVC Framework</p>
		</div>
	</div>
	<div class="row">
		<div class="span12">
			<?php
				foreach($notifications as $n) {
					$E('<div class="alert alert-'.$n[1].'"><i class="icon-'.$n[1]
						.($n[1]=='success' ? ' icon-ok-sign' : ' icon-exclamation-sign')
						.'"></i> '.$F($n[0],'decode').'</div>');
				}
				$E($F($CONTENT,'decode'));
			?>
		</div>
	</div>
</div>

<?php $E($this->headers->getFooters()); ?>
</body>
</html>