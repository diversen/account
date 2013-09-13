<?php

template::setTitle(lang::translate('Create new password'));

$request = new account_requestpw();
$res = $request->verifyAccount();
if ($res){
    $request->sanitize();
    if (isset($_POST['submit'])){        
        $request->validatePasswordFromPost();
        if (empty($request->errors)){
            if ($request->setNewPassword()){
                session::setActionMessage(
                    lang::translate('New password has been saved'), true
                );
                $location = config::getModuleIni('account_default_url');
                http::locationHeader($location);
            }
        } else {
            html::errors($request->errors);
        }
    }
    account_requestpw_views::formVerify();
} else {
    html::errors($request->errors);
}
