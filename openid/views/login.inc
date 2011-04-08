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

<?php

$rows = array (
    0 => array (
        'id' => 'select', 'title' => lang::translate('account_openid_select_provider')),
    1 => array (
        'id' => 'https://www.google.com/accounts/o8/id', 'title' => 'Google'),
    2 => array (
        'id' => 'https://me.yahoo.com', 'title' => 'yahoo!'),
);

$str = '';
//$str.= lang::translate('account_openid_select_provider');
$str.= '<form method="post" action="">';
$str.= "<fieldset>\n";
$str.= "<legend>" . lang::translate('account_openid_enter_provider') . "</legend>";

$extras = array();
$extras[0] = array('id' => '0', 'title' => lang::translate('account_openid_openid_provider_options'));

$str.= view_drop_down(
    'openid_identifier',
    $rows,
    'title',
    'id',
    $extras,
    'onChange="this.form.submit()"');

$str.= '<input type="hidden" name="openid_action" value="login">';
$str.= '</fieldset>';
$str.= '</form>';

echo $str;

?>

<form method="post" action="">
<fieldset>
<legend><?=lang::translate('account_login_open_id_legend')?></legend>
<label for="openid"><?php echo lang::translate('account_open_id_your_open_id_form')?>:</label><br />
<input type="text" name="openid_identifier" size="30" value="<?=@$_POST[openid_identifier]?>"><br />

<label for="keep_session"><?=lang::translate('account_keep_session')?> <?=get_main_ini('cookie_time')?> <?=lang::translate('account_num_days')?></label><br />
<input name="keep_session" <?=$keep_session?> type="checkbox">

<label for="submit"></label><br />
<input type="submit" name="openid_action" value="login"><br />
</fieldset>
</form>
</div>