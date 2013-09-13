<?php

if (!session::checkAccessControl('account_allow_edit')){
    moduleloader::setStatus(403);
    return;
}

if (config::getModuleIni('account_disable_admin_interface')) {
    moduleloader::setStatus(403);
    return;
}

template::setTitle(lang::translate('Edit account'));

$l = new account_admin();
$user = $l->getUser();

if (isset($_POST['submit'])){
    if (empty($user['url'])){
        $l->validate();
    }
    if (empty($l->errors)){

        if (!empty($user['url'])){

            $res = $l->updateUrlUser();
        } else {
            $res = $l->updateEmailUser();
        }
        if ($res){
            session::setActionMessage(
                lang::translate('Account has been updated')
            );
            http::locationHeader('/account/admin/list');
        }
    } else {
        html::errors($l->errors);
    }
}


if (!empty($user['url'])){  
    account_admin_views::updateUrlUser($user);
} else {   
    account_admin_views::updateEmailUser($user);
}