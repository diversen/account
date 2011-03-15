<?php

/**
 * controller file for admin/edit
 * 
 * @package    account
 */
if (!session::checkAccessControl('allow_edit')){
    return;
}

template::setTitle(lang::translate('Edit User'));

$account = new accountAdmin();
$user = $account->getUser();

if (isset($_POST['submit'])){
    
    if (empty($user['url'])){
    
        // only validate if user is a email user
        // on url user we can only set if he is admin and super
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
                lang::translate('User Updated')
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



