<?php

moduleLoader::includeModule('account/create');
if (!session::checkAccessControl('account_allow_create')){
    return;
}

template::setTitle(lang::translate('account_create_index_title'));
$l = new accountCreate();
if (!empty($_POST['submit'])){
    $_POST = html::specialEncode($_POST);
    $l->validate();
    if (empty($l->errors)){
        $l->createUser();
        view_confirm(lang::translate('account_create_account_has_been_created'));
    } else {
        view_form_errors($l->errors);
        echo view::get('account', 'create/create');
    }
} else {
    echo view::get('account', 'create/create');
}
