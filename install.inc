<?php

$account_default_url = config::getModuleIni('account_default_url');
$account_admin_only = config::getModuleIni('account_admin_only');
$account_link_auth = config::getModuleIni('account_link_auth');
$account_allow_edit = config::GetModuleIni('account_allow_edit');

if (!$account_default_url){
    $account_default_url = "/account/login/index";
}

$_INSTALL['MAIN_MENU_ITEMS'] = array ();
$_INSTALL['MAIN_MENU_ITEMS'][0] = array (
    'title' => lang::translate('account', null, array ('no_translate' => true)),
    'url' => $account_default_url,
    'module_name' => 'account',
    'parent' => 0,
    'weight' => 1,
    'section' => 'account',
    'auth' => 'anon'
);

// who can se the account link
if ($account_link_auth){
    $_INSTALL['MAIN_MENU_ITEMS'][0]['auth'] = $account_link_auth;
}

// admin menu item
$_INSTALL['MAIN_MENU_ITEMS'][1] = array (
    'title' => lang::translate('Users', null, array ('no_translate' => true)),
    'url' => '/account/admin/list',
    'module_name' => 'account',
    'parent' => 0,
    'weight' => 1,
    'section' => 'account',
    'auth' => $account_allow_edit,
    'admin_only' => 1
);



if (config::getModuleIni('account_logout_link')) {
    $_INSTALL['MAIN_MENU_ITEMS'][] = array(
        'title' => lang::translate('Logout', null, array('no_translate' => true)),
        'url' => '/account/logout',
        'module_name' => 'account',
        'parent' => 0,
        'weight' => 1,
	'auth' => 'user'
    );
}




$_INSTALL['VERSION'] = 2.41;
$_INSTALL['PUBLIC_CLONE_URL'] = 'git://github.com/diversen/account.git';
$_INSTALL['PRIVATE_CLONE_URL'] = 'git@github.com:diversen/account.git';
