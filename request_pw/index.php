<?php

/**
 * @package    account
 */
template::setTitle(lang::translate('account_request_password_title'));

$request = new request();
if (isset($_POST['submit'])){
    $request->sanitize();
    $request->validate();
    
    if (empty($request->errors)){
        $mail_sent = $request->requestPassword($_POST['email']);
        if ($mail_sent){
            session::setActionMessage(
                lang::translate('account_request_login_info_sent_to') .
                MENU_SUB_SEPARATOR_SEC .
                $_POST['email']
            );
            $location = get_module_ini('account_default_url');
            $header = "Location: $location";
            header($header);
        }
    } else {
        view_form_errors($request->errors);
    }
}

view_request();