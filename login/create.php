<?php

/**
 * controller file for creating a user
 */

http::prg();

moduleloader::includeModule('account/create');
if (!session::checkAccessFromModuleIni('account_allow_create')){
    return;
}

template::setTitle(lang::translate('Create Account'));
$l = new account_create();
if (!empty($_POST['submit'])){
    $_POST = html::specialEncode($_POST);
    $l->validate();
    if (empty($l->errors)){
        $l->createUser();
        http::locationHeader(
                '/account/login/index',
                lang::translate('Account: Create notice'));
    } else {
        html::errors($l->errors);
    }
}

account_login_views::formCreate();
echo account_views::getTermsLink();
