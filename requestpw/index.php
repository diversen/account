<?php

/**
 * @package    account
 */

requestpw::disaplyRequestPassword();
return;

template::setTitle(lang::translate('account_request_password_title'));

$request = new requestpw();
if (isset($_POST['submit'])){
    $request->sanitize();
    $request->validate();
    
    if (empty($request->errors)){
        $mail_sent = $request->requestPassword($_POST['email']);
        if ($mail_sent){
            session::setActionMessage(
                lang::translate('account_request_login_info_sent_to')
            );
            $location = config::getModuleIni('account_default_url');
            $header = "Location: $location";
            header($header);
        } else {
            cos_debug("could not send mail:" . __FILE__);
        }
    } else {
        view_form_errors($request->errors);
    }
}

$request->displayRequest();
//view_request();