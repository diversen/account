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
