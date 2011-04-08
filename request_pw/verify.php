<?php

/**
 * @package    account
 */
template::setTitle(lang::translate('account_request_set_new_passwords_title'));

$request = new request();
$res = $request->verifyAccount();
if ($res){
    if (isset($_POST['submit'])){
        $request->validate();
        $request->validatePassword();
        if (!empty($request->errors)){
            view_form_errors($request->errors);
            view_verify();
        } else {
            if ($request->setNewPassword()){
                session::setActionMessage(
                    lang::translate('account_request_new_password_has_been_set')
                );
                header("Location: /account/index");
            }
        }
    } else {
        view_verify();
    }
} else {
    view_form_errors($request->errors);
}







