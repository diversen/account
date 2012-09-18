<?php

template::setTitle(lang::translate('account_request_set_new_passwords_title'));

$request = new accountRequestpw();
$res = $request->verifyAccount();
if ($res){
    $request->sanitize();
    if (isset($_POST['submit'])){        
        $request->validatePassword();
        if (empty($request->errors)){
            if ($request->setNewPassword()){
                session::setActionMessage(
                    lang::translate('account_request_new_password_has_been_set'), true
                );
                $location = config::getModuleIni('account_default_url');
                $header = "Location: $location";
                header($header);
            }
        } else {
            view_form_errors($request->errors);
        }
    }
    $request->displayNewPassword();
} else {

    view_form_errors($request->errors);
}

