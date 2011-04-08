<?php

/**
 * @package    account
 */

/**
 * include captcha lib
 */
include_once "lib/captcha.php";

/**
 * view function for showing form forgetting lost password
 */
function view_request (){ ?>
<div id="form_main">
<form method="post" action="">
<fieldset>
<legend><?php echo lang::translate('account_enter_email_to_recover_lost_password')?>:</legend>
<label for="email"><?php echo lang::translate('account_form_email')?>:</label><br />
<input type="text" name="email" value="<?php echo htmlentities($_POST['email'])?>"><br />
<label for="captcha"><?php echo captcha::createCaptcha()?></label><br />
<input type="text" name="captcha" value="<?php echo htmlspecialchars($_POST['captcha'])?>"><br />
<label for="submit"></label><br />
<input type="submit" name="submit" value="<?php echo lang::translate('account_submit')?>"><br />
</fieldset>
</form>
</div>
<?php

}

/**
 * view function for setting new password
 */
function view_verify (){ 
    if (!isset($_POST['password'])){
        $_POST['password'] = '';
    }
    if (!isset($_POST['password2'])){
        $_POST['password2'] = '';
    } ?>
<div id="form_main">
<form method="post" action="">
<fieldset>
<legend><?php echo lang::translate('account_request_legend_set_new_password') ?></legend>
<label for="password"><?php echo lang::translate('account_form_password')?>:</label><br />
<input type="password" name="password" value="<?php echo htmlspecialchars($_POST['password'])?>"><br />
<label for="password2"><?php echo lang::translate('account_form_retype_password')?>:</label><br />
<input type="password" name="password2" value="<?php echo htmlspecialchars($_POST['password2'])?>"><br />
<label for="submit"></label><br />
<input type="submit" name="submit" value="<?php echo lang::translate('account_submit')?>"><br />
</fieldset>
</form>
</div>
<?php

}