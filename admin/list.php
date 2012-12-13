<?php

/**
 * @package    account
 */
if (!session::checkAccessControl('account_allow_edit')){
    moduleLoader::setStatus(403);
    return;
}

if (config::getModuleIni('account_disable_admin_interface')) {
    moduleLoader::setStatus(403);
    return;
}

//$p = new pearPager($total);
$a = new accountAdmin();
$num_rows = $a->getNumUsers();

$p = new pearPager($num_rows);
$users = $a->getUsers($p->from);

template::setTitle(lang::translate('account_list_users'));
viewAccountAdmin::listUsers($users);

echo $p->getPagerHTML();