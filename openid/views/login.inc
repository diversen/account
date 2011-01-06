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
    1 => array (
        'id' => 'https://www.google.com/accounts/o8/id', 'title' => 'Google'),
    2 => array (
        'id' => 'https://me.yahoo.com', 'title' => 'yahoo!'),
    /*
    3 => array (
        'id' => 'http://www.flickr.com/username', 'title' => 'Flickr'),
    4 => array (
        'id' => 'http://openid.aol.com/username', 'title' => 'AOL'),
    5 => array ('id' => 'https://username.wordpress.com/', 'title' => 'Wordpress')
     * 
     */
);

$str = '';
//$str.= lang::translate('account_openid_select_provider');
$str.= '<form method="post" action="">';
$str.= "<fieldset>\n";
$str.= "<legend>" . lang::translate('account_openid_select_provider') . "</legend>";

$extras = array();
$extras[0] = array('id' => '0', 'title' => lang::translate('account_openid_select_openid_provider'));

$str.= view_drop_down(
    'openid_identifier',
    $rows,
    'title',
    'id',
    $extras,
    'onChange="this.form.submit()"');

$str.= '<input type="hidden" name="openid_action" value="login">';
//$str.= '<input type="submit" name="submit_form" value="' . lang::translate('Search') . '" />';
$str.= '</fieldset>';
$str.= '</form>';

echo $str;

?>

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
