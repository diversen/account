<?php

/**
 * @ignore
 */


$account_logins = config::getModuleIni('account_logins');
$account_show_admin_link = config::getModuleIni('account_show_admin_link');

$_MODULE_MENU = array();

if (in_array('email', $account_logins)){
    $_MODULE_MENU[0] = array(
        'title' => lang::translate('account_login_with_email'),
        'url' => '/account/login/index'
    );
}

if (in_array('openid', $account_logins)){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('account_use_open_id'),
        'url' => '/account/openid/index'
    );
}

if (in_array('lightopenid', $account_logins)){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('account_use_open_id'),
        'url' => '/account/lightopenid/index'
    );
}

if (in_array('facebook', $account_logins)){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('account_login_facebook'),
        'url' => '/account/facebook/index'
    );
}

$_MODULE_MENU[] = array(
    'title' => lang::translate('account_admin'),
    'url' => '/account/admin/index',
    'auth' => $account_show_admin_link,
);

ksort ($_MODULE_MENU);