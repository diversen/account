<?php

use diversen\conf;
use diversen\lang;
use diversen\session;

if (!session::isUser()) {
    $_SUB_MODULE_MENU[] = array(
        'title' => lang::translate('Lost password'),
        'url' => '/account/login/requestpw'
    );
}

$anon_create = conf::getModuleIni('account_anon_create');
/*
if (!$anon_create) {
    $anon_create = 'anon';
}*/

if (!session::isUser() && $anon_create) {
    $_SUB_MODULE_MENU[] = array(
        'title' => lang::translate('Create account'),
        'url' => '/account/login/create',
        'auth' => 'anon'
    );
}