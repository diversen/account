<?php

if (!session::checkAccessControl('account_allow_create')){
    return;
}

template::setTitle(lang::translate('Create Account'));
$account = new accountCreate();
if (!empty($_POST['submit'])){
    $account->validate();
    if (empty($account->errors)){
        $account->createUser();
        view_confirm(lang::translate('New account has been created'));
    } else {
        view_form_errors($account->errors);
        view_create();
    }
} else {
    view_create();
}