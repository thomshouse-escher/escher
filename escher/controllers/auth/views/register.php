<h1>Register</h1>
<form method="POST" class="logregform registerform">
	<fieldset>
		<div>
			<label>Username:</label>
			<input type="text" name="username" value="<?php $E(@$username); ?>"<?php
				if(in_array('username',$errors)) echo ' class="error"'; ?> />
		</div>
		<div>
			<label>Full Name:</label>
			<input type="text" name="full_name" value="<?php $E(@$full_name); ?>" />
		</div>
		<div>
			<label>E-mail:</label>
			<input type="text" name="email" value="<?php $E(@$email); ?>"<?php
				if(in_array('email',$errors)) echo ' class="error"'; ?> />
		</div>
	</fieldset>
	<fieldset>
		<legend>Password Confirmation</legend>
		<div>
			<label>Password:</label>
			<input type="password" name="password"<?php
				if(in_array('password',$errors)) echo ' class="error"'; ?> />
		</div>
		<div>
			<label>Confirm:</label>
			<input type="password" name="password2"<?php
				if(in_array('password2',$errors)) echo ' class="error"'; ?> />
		</div>
		<div><input class="no-label" type="submit" value="Register" />
	</fieldset>
</form>