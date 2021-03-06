<?php

use diversen\conf;
use diversen\lang;
use diversen\session;

$allow = conf::getModuleIni('account_allow_create');

$_SUB_MODULE_MENU[] = array(
        'title' => lang::translate('Users'),
        'url' => '/account/admin/index',
        'auth' => $allow
);

$_SUB_MODULE_MENU[] = array(
        'title' => lang::translate('Search'),
        'url' => '/account/admin/search',
        'auth' => $allow
);

$_SUB_MODULE_MENU[] = array(
        'title' => lang::translate('Create user'),
        'url' => '/account/admin/create',
        'auth' => $allow
);

if (conf::getModuleIni('account_domain_allow')) {
    $_SUB_MODULE_MENU[] = array(
        'title' => lang::translate('Domain'),
        'url' => '/account/admin/domain',
        'auth' => 'super'
    );
}

if (conf::getModuleIni('account_user_limit')) {
    $_SUB_MODULE_MENU[] = array(
        'title' => lang::translate('Number of users'),
        'url' => '/account/admin/usernum',
        'auth' => 'super'
    );
}
