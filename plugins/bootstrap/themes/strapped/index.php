<!DOCTYPE HTML>
<html lang="en" class="<?php echo $useragent_classes; ?>">
<head>
<title><?php $E($this->headers->getTitle()); ?></title>
<?php
$this->headers->addMeta('viewport','width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable = no;');
$this->headers->addMeta('Content-Type','text/html;charset=utf-8');
$this->headers->addLink('stylesheet',"$theme_dir/css/bootstrap.css");
$this->headers->loadJQuery();
$this->headers->addJS($theme_dir.'/js/bootstrap.min.js');
$E($this->headers->getHeaders()); ?>
</head>
<body>

<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
			<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</a>
			<a class="brand" href="<?php $E($www); ?>"><?php $UI->title(); ?></a>
			<div class="nav-collapse">
				<ul class="nav">
				  <li><a href="#">Home</a></li>
				</ul>
				<ul class="nav pull-right">
					<?php if($USER) { ?>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php
							$E($USER['display_name']); ?> <b class="caret"></b></a>
						<ul class="dropdown-menu">
							<?php if($_require('all','sysadmin')) { ?>
							<li><a href="<?php $E($U('@admin/')); ?>">Site Admin</a></li>
							<?php } ?>
							<li><a href="<?php $E($www.'/logout/'); ?>">Log out</a></li>
						</ul>
					</li>
					<?php } else { ?>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">Log in <b class="caret"></b></a>
						<ul class="dropdown-menu">
							<li><a href="<?php $E($www.'/login/'); ?>">Sign in</a></li>
							<?php if ($auth_choices = $H('auth_choices')) {
								foreach($auth_choices as $auth) { ?>
							<li class="login-button"><?php $E($auth); ?></li>
							<?php }} ?>
							<li><a href="<?php $E($www.'/signup/'); ?>">Sign up</a></li>
						</ul>
					</li>
					<?php } ?>
				</ul>
			</div>
		</div>
	</div>
</div>

<div class="container" id="strapped-content">
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
	<div class="row">
		<div class="span12" id="strapped-footer">
			Copyright &copy; <?php $E(date('Y')); ?>. All rights reserved.
		</div>
	</div>
</div>

<div class="container">
	<div class="row" id="powered-by-escher">
		<div class="span12">
			<a href="http://www.escherphp.com" target="_blank">Powered by Escher</a>
		</div>
	</div>
</div>
<?php $E($this->headers->getFooters()); ?>
</body>
</html>