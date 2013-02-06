<?php

/**
 * @package    account
 */
if (!session::checkAccessControl('account_allow_edit')){
    return;
}

if (config::getModuleIni('account_disable_admin_interface')) {
    moduleloader::setStatus(403);
    return;
}
// account_disable_admin_interface

template::setTitle(lang::translate('account_delete_account_title'));

$l = new accountAdmin();
$l->verifyAccount();
$user = $l->getUser();

if (!empty($_POST['submit'])){
    $l->deleteUser();
    http::locationHeader(
            '/account/admin/list', 
            lang::translate('account_has_been_deleted'));
} else {
    viewAccountAdmin::delete($user);
}