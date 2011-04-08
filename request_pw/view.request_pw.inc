<?php

include_once "captcha.php";
include_once "html.php";
/**
 * view function for showing form forgetting lost password
 */
/*
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
*/
function view_request (){
    $field_size = array ('size' => '30');
    html::$autoLoadTrigger = 'submit';
    html::init($_POST);
    html::formStart('blog_form');
    html::legend(lang::translate('account_user_create_legend'));
    html::label('email', lang::translate('account_email_label'));
    html::text('email',  '', $field_size);
    html::label('captcha', captcha::createCaptcha());
    html::text('captcha', '', array ('size' => 1, 'maxlength'=> 2));
    html::label('submit', '');
    html::submit('submit', lang::translate('account_submit'));
    html::formEnd();
    echo html::$formStr;
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