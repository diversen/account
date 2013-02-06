<?php

if (!session::checkAccessControl('account_allow_edit')){
    moduleloader::setStatus(403);
    return;
}

if (config::getModuleIni('account_disable_admin_interface')) {
    moduleloader::setStatus(403);
    return;
}

template::setTitle(lang::translate('account_edit_account_title'));

$l = new accountAdmin();
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
                lang::translate('account_action_user_updated')
            );
            http::locationHeader('/account/admin/list');
        }
    } else {
        view_form_errors($l->errors);
    }
}


if (!empty($user['url'])){  
    viewAccountAdmin::updateUrlUser($user);
} else {   
    viewAccountAdmin::updateEmailUser($user);
}