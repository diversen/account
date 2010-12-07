<?php

/**
 * @package    account
 */
if (!session::checkAccessControl('allow_edit')){
    return;
}

template::setTitle(lang::translate('Delete User'));

$account = new accountAdmin();
$user = $account->getUser();

if (!empty($_POST['submit'])){
    $account->deleteUser();
    view_confirm(lang::translate('User Deleted'));
} else {
    viewAccountAdmin::delete($user);
}