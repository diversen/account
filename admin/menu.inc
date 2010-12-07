<?php

/**
 * @package    account
 */
//$allow_edit = get_module_conf('allow_edit');
$allow_edit = register::$vars['coscms_main']['module']['allow_edit'];
$_SUB_MODULE_MENU = array (
    1 => array(
        'title' => lang::translate('account_list_users'),
        'url' => '/account/admin/list',
        'auth' => $allow_edit
    ),
);

$use_openid = get_module_ini('use_openid');
if ($use_openid){
    $_SUB_MODULE_MENU[2] = array(
        'title' => lang::translate('account_list_openids'),
        'url' => '/account/admin/openid_list',
        'auth' => $allow_edit
    );
}