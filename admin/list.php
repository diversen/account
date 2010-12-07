<?php

/**
 * @package    account
 */
if (!session::checkAccessControl('allow_edit')){
    return;
}

$a = new accountAdmin();
$users = $a->getUsers();

template::setTitle(lang::translate('List Users'));
viewAccountAdmin::users($users);
