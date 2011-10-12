<h1>Sign Up</h1>
<form method="POST" class="logregform registerform">
	<fieldset>
		<div class="inputs">
			<label>Username:</label>
			<div class="input">
				<input type="text" name="username" value="<?php $E(@$username); ?>"<?php
					if(in_array('username',$errors)) echo ' class="error"'; ?> />
			</div>
		</div>
		<div class="inputs">
			<label>Full Name:</label>
			<div class="input">
				<input type="text" name="full_name" value="<?php $E(@$full_name); ?>" />
			</div>
		</div>
		<div class="inputs">
			<label>Email:</label>
			<div class="input">
				<input type="text" name="email" value="<?php $E(@$email); ?>"<?php
					if(in_array('email',$errors)) echo ' class="error"'; ?> />
			</div>
		</div>
		<div class="inputs">
			<label>Password:</label>
			<div class="input">
				<input type="password" name="password" autocomplete="off"<?php
					if(in_array('password',$errors)) echo ' class="error"'; ?> />
			</div>
		</div>
	</fieldset>
<?php 
	$captcha = $H('captcha_display');
	if (sizeof($captcha)>0) { ?>
	<fieldset>
		<legend>Captcha</legend>
<?php $E(implode('',$captcha)); ?>
	</fieldset>
<?php } ?>
	<div class="actions">
		<div class="agree-terms clearfix">
			<ul class="inputs-list"><li>
				<input type="checkbox" name="agree_terms" value="1"<?php
					if ($agree_terms) { ?> checked="checked"<?php } ?> />
				By checking this box, you acknowledge that you have read and accept the
					<a href="<?php $E($www.'/terms.txt'); ?>" target="_blank">terms of service</a>
				of this website.
			</li></ul>
		</div>
		<div class="register-submit"><input class="btn large primary" type="submit" value="Sign up" /></div>
	</div>
</form>