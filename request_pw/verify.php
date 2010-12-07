<?php

/**
 * @package    account
 */
template::setTitle(lang::translate('Set New Password'));

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
                    lang::translate('New passwords has been set')
                );
                header("Location: /account/index");
                //view_confirm(lang::translate('New passwords has been set'));
                //$password_set = 1;
            }
        }
    } else {
        view_verify();
    }
} else {
    view_form_errors($request->errors);
}







