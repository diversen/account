<?php

moduleLoader::includeModule('account/create');
if (!session::checkAccessControl('account_allow_create')){
    return;
}

template::setTitle(lang::translate('account_create_index_title'));
$account = new accountCreate();
if (!empty($_POST['submit'])){
    $account->validate();
    if (empty($account->errors)){
        $account->createUser();
        view_confirm(lang::translate('account_create_account_has_been_created'));
    } else {
        view_form_errors($account->errors);
        account_view_create();
    }
} else {
    account_view_create();
}