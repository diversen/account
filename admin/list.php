<?php

/**
 * @package    account
 */
if (!session::checkAccessControl('account_allow_edit')){
    moduleloader::setStatus(403);
    return;
}

if (config::getModuleIni('account_disable_admin_interface')) {
    moduleloader::setStatus(403);
    return;
}

$_GET = html::specialEncode($_GET);

$a = new account_admin();

$a->searchAccount();
if (isset($_GET['submit'])) {
    $acc = new account();

    $res = $acc->searchIdOrEmail($_GET['id']);
    if (!empty($res)) {
        echo lang::translate('account: admin: found results');
        AccountAdminViews::listUsers($res);
        echo "<hr />\n";
        
    } else {
        echo lang::translate('account: admin: found no results');
    }   
}


$num_rows = $a->getNumUsers();

$p = new pearPager($num_rows);
$users = $a->getUsers($p->from);

template::setTitle(lang::translate('account_list_users'));
AccountAdminViews::listUsers($users);

echo $p->getPagerHTML();