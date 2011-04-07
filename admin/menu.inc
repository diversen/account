<?php

$account_allow_edit = get_module_ini('account_allow_edit');
$_SUB_MODULE_MENU = array (
    1 => array(
        'title' => lang::translate('account_list_users'),
        'url' => '/account/admin/list',
        'auth' => $account_allow_edit
    ),
);