<?php

/**
 * @ignore
 */

//moduleLoader::getModuleIniSettings('account');

$account_allow_edit = config::getModuleIni('account_allow_edit');

$account_show_admin_link = config::getModuleIni('account_show_admin_link');
$account_use_openid_login = config::getModuleIni('account_use_openid_login');
$account_use_email_login = config::getModuleIni('account_use_email_login');
$account_use_facebook_login = config::getModuleIni('account_use_facebook_login');

$_MODULE_MENU = array();

if ($account_use_email_login){
    $_MODULE_MENU[0] = array(
        'title' => lang::translate('account_login_with_email'),
        'url' => '/account/login/index'
    );

    
    /*
    $_MODULE_MENU[2] = array(
        'title' => lang::translate('account_request_password'),
        'url' => '/account/requestpw/index'
    );*/

}

if ($account_use_openid_login){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('account_use_open_id'),
        'url' => '/account/openid/index'
    );
}

if ($account_use_facebook_login){
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