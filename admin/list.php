<?php

/**
 * @package    account
 */
if (!session::checkAccessControl('account_allow_edit')){
    return;
}

if (config::getModuleIni('account_disable_admin_interface')) {
    moduleLoader::setStatus(403);
    return;
}

$a = new accountAdmin();
$users = $a->getUsers();

template::setTitle(lang::translate('account_list_users'));
viewAccountAdmin::users($users);
