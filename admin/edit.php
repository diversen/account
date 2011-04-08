<?php

if (!session::checkAccessControl('account_allow_edit')){
    return;
}

template::setTitle(lang::translate('account_edit_account_title'));

$account = new accountAdmin();
$user = $account->getUser();

if (isset($_POST['submit'])){
    if (empty($user['url'])){
        $account->validate();
    }
    if (empty($account->errors)){

        if (!empty($user['url'])){

            $res = $account->updateUrlUser();
        } else {
            $res = $account->updateEmailUser();
        }
        if ($res){
            session::setActionMessage(
                lang::translate('account_action_user_updated')
            );
            header("Location: /account/admin/list");
        }
    } else {
        view_form_errors($account->errors);
    }
}

if (!empty($user['url'])){
    viewAccountAdmin::updateUrlUser($user);
} else {   
    viewAccountAdmin::updateEmailUser($user);
}