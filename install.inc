<?php

$account_show_menu = get_module_ini('account_show_menu');
$account_default_url = get_module_ini('account_default_url');
$account_admin_only = get_module_ini('account_admin_only');

if (!$account_default_url){
    $account_default_url = "/account/login/index";
}

$_INSTALL['MAIN_MENU_ITEM'] = array (
    'title' => 'Account',
    'url' => $account_default_url,
    'module_name' => 'account',
    'parent' => 0,
    'weight' => 1,
    'auth' => $account_show_menu
);

if ($account_admin_only){
    $_INSTALL['MAIN_MENU_ITEM']['admin_only'] = 1;
}

$_INSTALL['VERSION'] = 1.31;
$_INSTALL['PUBLIC_CLONE_URL'] = 'git://github.com/diversen/account.git';
$_INSTALL['PRIVATE_CLONE_URL'] = 'git@github.com:diversen/account.git';