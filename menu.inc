<?php

/**
 * @ignore
 */

moduleLoader::getModuleIniSettings('account');

$account_allow_edit = get_module_ini('account_allow_edit');
$account_allow_create = get_module_ini('account_allow_create');
$account_show_admin_link = get_module_ini('account_show_admin_link');
$account_use_openid_login = get_module_ini('account_use_openid_login');
$account_use_email_login = get_module_ini('account_use_email_login');
$account_use_facebook_login = get_module_ini('account_use_facebook_login');

$_MODULE_MENU = array();

if ($account_use_email_login){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('account_login_with_email'),
        'url' => '/account/login/index'
    );
    
    $_MODULE_MENU[] = array(
            'title' => lang::translate('account_create_email_account'),
            'url' => '/account/create/index',
            'auth' => get_module_ini('account_allow_create'),
        );

    $_MODULE_MENU[] = array(
        'title' => lang::translate('account_request_password'),
        'url' => '/account/request_pw/index'
    );

}

if (isset($account_use_openid_login)){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('account_use_open_id'),
        'url' => '/account/openid/index'
    );
}

if (isset($account_use_facebook_login)){
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