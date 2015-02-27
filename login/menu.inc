<?php

if (!session::isUser()) {
    $_SUB_MODULE_MENU[] = array(
        'title' => lang::translate('Lost password'),
        'url' => '/account/login/requestpw'
    );
}

$allow_create = config::getModuleIni('account_allow_create');
if (!$allow_create) {
    $allow_create = 'anon';
}

if (!session::isUser()) {
    $_SUB_MODULE_MENU[1] = array(
        'title' => lang::translate('Create account'),
        'url' => '/account/login/create',
        'auth' => $allow_create
    );
}