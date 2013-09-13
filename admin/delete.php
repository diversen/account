<?php

/**
 * @package    account
 */
if (!session::checkAccessControl('account_allow_edit')){
    return;
}

// account_disable_admin_interface
if (config::getModuleIni('account_disable_admin_interface')) {
    moduleloader::setStatus(403);
    return;
}

template::setTitle(lang::translate('Delete account'));

$l = new account_admin();
$user = $l->getUser();

if (!empty($_POST['submit'])){
    $l->deleteUser();
    http::locationHeader(
            '/account/admin/list', 
            lang::translate('Account has been deleted'));
} else {
    account_admin_views::delete($user);
}