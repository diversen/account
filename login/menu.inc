<?php

if (!session::isUser()) {
    $_SUB_MODULE_MENU[] = array(
        'title' => lang::translate('Lost password'),
        'url' => '/account/login/requestpw'
    );
}

// can we create accounts. And who is allowed to create accounts. 
$account_allow_create = config::getModuleIni('account_allow_create');
if ($account_allow_create) {
    $_SUB_MODULE_MENU[1] = array(
        'title' => lang::translate('Create account'),
        'url' => '/account/login/create',
        'auth' => $account_allow_create
    );
} 