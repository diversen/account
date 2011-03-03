<?php

/**
 * @package    account
 */

// who can see the account login main menu item
$show_menu = get_module_ini('show_menu');

// set default login url if any
$login_url = get_module_ini('account_default_url');
if (!$login_url){
    $login_url = "/account/login/index";
}

$_INSTALL['MAIN_MENU_ITEM'] = array (
    'title' => 'Account',
    'url' => $login_url,
    'module_name' => 'account',
    'parent' => 0,
    'weight' => 1,
    'auth' => $show_menu
);

$_INSTALL['VERSION'] = 1.03;

$_INSTALL['PUBLIC_CLONE_URL'] = 'git://github.com/diversen/account.git';
$_INSTALL['PRIVATE_CLONE_URL'] = 'git@github.com:diversen/account.git';


