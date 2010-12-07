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
<form method="post">
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
</div><!--<?php echo lang::translate('Submit')?>-->