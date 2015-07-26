<?php

$account_allow_edit = conf::getModuleIni('account_allow_edit');
$_SUB_MODULE_MENU = array (
    1 => array(
        'title' => lang::translate('Search for users'),
        'url' => '/account/admin/list',
        'auth' => $account_allow_edit
    ),
);