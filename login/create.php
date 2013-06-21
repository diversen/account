<?php

/**
 * controller file for creating a user
 */

http::prg();
moduleloader::includeModule('account/create');
if (!session::checkAccessControl('account_allow_create')){
    return;
}

template::setTitle(lang::translate('account_create_index_title'));
$l = new account_create();
if (!empty($_POST['submit'])){
    $_POST = html::specialEncode($_POST);
    $l->validate();
    if (empty($l->errors)){
        $l->createUser();
        http::locationHeader(
                '/account/login/index',
                lang::translate('account_create_account_has_been_created'));
    } else {
        html::errors($l->errors);
    }
}

account_login_views::formCreate();
