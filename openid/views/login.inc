<?php

/**
 * @package account
 *
 * @abstract
 *
 * View file for showing an open account login box
 * 
 */

if (isset($_POST['openid_action'])){
    if (isset($_POST['keep_session'])){
        $keep_session = 'checked="checked"';
    } else {
        $keep_session = '';
    }

} else {
    $keep_session = 'checked="checked"';
}

?>
<div id="form_main">
<table>
<tr>
    <td colspan="2">
Press your openid provider:
    </td>
</tr><td>
<form method="post" action="">
<input type="hidden" name="openid_identifier" size="30" value="https://www.google.com/accounts/o8/id"><br />
<input type="image" src="/images/google_login.jpg"name="openid_action" value="login"><br />
</form>

</td><td>

<form method="post" action="">
<input type="hidden" name="openid_identifier" size="30" value="http://me.yahoo.com/"><br />
<input type="image" src="/images/yahoo_login.jpg"name="openid_action" value="login"><br />
</form>
</td></tr>
</table>



<form method="post" action="">
<fieldset>
<legend><?=lang::translate('login_open_id')?></legend>
<label for="openid"><?php echo lang::translate('your_open_id')?>:</label><br />
<input type="text" name="openid_identifier" size="30" value="<?=@$_POST[openid_identifier]?>"><br />

<label for="keep_session"><?=lang::translate('keep_session')?> <?=get_main_ini('cookie_time')?> <?=lang::translate('days')?></label><br />
<input name="keep_session" <?=$keep_session?> type="checkbox">

<label for="submit"></label><br />
<input type="submit" name="openid_action" value="login"><br />
</fieldset>
</form>





</div>
