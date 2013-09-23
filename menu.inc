<?php

/**
 * @ignore
 */

// load ini settings
moduleloader::includeModule('account');

$account_logins = config::getModuleIni('account_logins');
$account_show_admin_link = config::getModuleIni('account_show_admin_link');

$_MODULE_MENU = array();

if (in_array('email', $account_logins)){
    $_MODULE_MENU[0] = array(
        'title' => lang::translate('Email login'),
        'url' => '/account/login/index'
    );
}

if (in_array('openid', $account_logins)){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('OpenID'),
        'url' => '/account/openid/index'
    );
}

if (in_array('lightopenid', $account_logins)){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('OpenID'),
        'url' => '/account/lightopenid/index'
    );
}

if (in_array('facebook', $account_logins)){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Facebook'),
        'url' => '/account/facebook/index'
    );
}

if (in_array('github', $account_logins)){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('Github'),
        'url' => '/account/github/index'
    );
}

if (!config::getModuleIni('account_disable_admin_interface')) {
    $_MODULE_MENU[] = array(
    'title' => lang::translate('Admin'),
    'url' => '/account/admin/list',
    'auth' => $account_show_admin_link);
}

ksort ($_MODULE_MENU);
