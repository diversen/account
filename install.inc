<?php

$account_default_url = config::getModuleIni('account_default_url');
$account_admin_only = config::getModuleIni('account_admin_only');

if (!$account_default_url){
    $account_default_url = "/account/login/index";
}

$_INSTALL['MAIN_MENU_ITEM'] = array (
    'title' => 'Account',
    'url' => $account_default_url,
    'module_name' => 'account',
    'parent' => 0,
    'weight' => 1,
    'section' => 'account'
);

if ($account_admin_only){
    $_INSTALL['MAIN_MENU_ITEM']['admin_only'] = 1;
}

$_INSTALL['VERSION'] = 1.711;
$_INSTALL['PUBLIC_CLONE_URL'] = 'git://github.com/diversen/account.git';
$_INSTALL['PRIVATE_CLONE_URL'] = 'git@github.com:diversen/account.git';
