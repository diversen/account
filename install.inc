<?php

use diversen\conf;
use diversen\lang;

lang::translate('account'); // for main menu translation

$account_default_url =  conf::getModuleIni('account_default_url');
$account_link_auth =    conf::getModuleIni('account_link_auth');


if (!$account_default_url){
    $account_default_url = "/account/login/index";
}

$_INSTALL['MAIN_MENU_ITEMS'] = array ();
$_INSTALL['MAIN_MENU_ITEMS'][0] = array (
    'title' => lang::translate('Account', null, array ('no_translate' => true)),
    'url' => $account_default_url,
    'module_name' => 'account',
    'parent' => 0,
    'weight' => 1,
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
    'auth' => 'super',
    'admin_only' => 1
);

if (conf::getModuleIni('account_logout_link')) {
    $_INSTALL['MAIN_MENU_ITEMS'][] = array(
        'title' => lang::translate('Logout', null, array('no_translate' => true)),
        'url' => '/account/logout',
        'module_name' => 'account',
        'parent' => 0,
        'weight' => 1,
	'auth' => 'user'
    );
}
