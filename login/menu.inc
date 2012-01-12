<?php

$_SUB_MODULE_MENU[] = array(
        'title' => lang::translate('account_request_password'),
        'url' => '/account/login/requestpw'
    );

$account_allow_create = config::getModuleIni('account_allow_create');
    $_SUB_MODULE_MENU[1] = array(
            'title' => lang::translate('account_create_email_account'),
            'url' => '/account/login/create',
        );

    if ($account_allow_create) {
        $_SUB_MODULE_MENU[1]['auth'] = $account_allow_create;
    } 