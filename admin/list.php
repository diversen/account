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

//$p = new pearPager($total);
$a = new accountAdmin();
$num_rows = $a->getNumUsers();

$p = new pearPager($num_rows);
$users = $a->getUsers($p->from);

template::setTitle(lang::translate('account_list_users'));
echo view::get('account', 'admin_list_users', $users);
echo $p->getPagerHTML();