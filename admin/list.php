<?php

use diversen\pagination as pearPager;
/**
 * @package    account
 */
if (!session::checkAccessFromModuleIni('account_allow_edit')){
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
        echo lang::translate('Found the following accounts');
        account_admin_views::listUsers($res);
        echo "<hr />\n";
        
    } else {
        echo lang::translate('I could not find any matching results');
    }   
}


$num_rows = $a->getNumUsers();

$p = new pearPager($num_rows);
$users = $a->getUsers($p->from);

template::setTitle(lang::translate('Search for users'));
account_admin_views::listUsers($users);

echo $p->getPagerHTML();
