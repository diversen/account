<?php

/**
 * @ignore
 */

moduleLoader::getModuleIniSettings('account');

$allow_edit = get_module_ini('allow_edit');
$allow_create = get_module_ini('allow_create');
$show_admin_link = get_module_ini('show_admin_link');
$use_openid_login = get_module_ini('use_openid_login');
$use_email_login = get_module_ini('use_email_login');
$use_facebook_login = get_module_ini('use_facebook_login');

$_MODULE_MENU = array();

if ($use_email_login){


    $_MODULE_MENU[] = array(
        'title' => lang::translate('account_login_with_email'),
        'url' => '/account/login/index'
    );
    
    $_MODULE_MENU[] = array(
            'title' => lang::translate('account_create_email_account'),
            'url' => '/account/create/index',
            'auth' => get_module_ini('allow_create'),
        );

    $_MODULE_MENU[] = array(
        'title' => lang::translate('account_request_password'),
        'url' => '/account/request_pw/index'
    );

}

if (isset($use_openid_login)){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('account_use_open_id'),
        'url' => '/account/openid/index'
    );
}

if (isset($use_facebook_login)){
    $_MODULE_MENU[] = array(
        'title' => lang::translate('account_login_facebook'),
        'url' => '/account/facebook/index'
    );
}

$_MODULE_MENU[] = array(
    'title' => lang::translate('account_admin'),
    'url' => '/account/admin/index',
    'auth' => $show_admin_link,
);

ksort ($_MODULE_MENU);