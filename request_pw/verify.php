<?php

template::setTitle(lang::translate('account_request_set_new_passwords_title'));

$request = new request();
$res = $request->verifyAccount();
if ($res){
    if (isset($_POST['submit'])){
        $request->sanitize();
        $request->validatePassword();
        if (empty($request->errors)){
            if ($request->setNewPassword()){
                session::setActionMessage(
                    lang::translate('account_request_new_password_has_been_set')
                );
                $location = get_module_ini('account_default_url');
                $header = "Location: $location";
                header($header);
            }
        } else {
            view_form_errors($request->errors);
        }
    } 
}

view_verify();