<?php

/**
 * view file for account/create
 *
 * @package    account
 */

/**
 * include captcha lib
 */
include_once "lib/captcha.php";

/**
 * function for showing a form for creating a user
 */
function view_create (){ ?>
<div id="form_main">
<form method="post" action="">
<fieldset>
<legend><?php echo lang::translate('Get an user account by filling out the following form') ?></legend>
<label for="username"><?php echo lang::translate('Username')?>:</label><br />
<input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'])?>"><br />
<label for="password"><?php echo lang::translate('Password')?>:</label><br />
<input type="password" name="password" value="<?php echo htmlspecialchars($_POST['password'])?>"><br />
<label for="password2"><?php echo lang::translate('Retype Password')?>:</label><br />
<input type="password" name="password2" value="<?php echo htmlspecialchars($_POST['password2'])?>"><br />
<label for="email"><?php echo lang::translate('Email')?>:</label><br />
<input type="text" name="email" value="<?php echo htmlspecialchars($_POST['email'])?>"><br />
<label for="captcha"><?php echo captcha::createCaptcha()?></label><br />
<input type="text" name="captcha" value="<?php echo htmlspecialchars($_POST['captcha'])?>"><br />
<label for="submit"></label><br />
<input type="submit" name="submit" value="<?php echo lang::translate('Submit')?>"><br />
</fieldset>
</form>
</div>
<?php

}