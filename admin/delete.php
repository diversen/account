<?php

/**
 * @package    account
 */
if (!session::checkAccessControl('account_allow_edit')){
    return;
}

template::setTitle(lang::translate('account_delete_account_title'));

$account = new accountAdmin();
$user = $account->getUser();

if (!empty($_POST['submit'])){
    $account->deleteUser();
    view_confirm(lang::translate('account_has_been_deleted'));
} else {
    viewAccountAdmin::delete($user);
}