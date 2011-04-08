<?php

/**
 * @package    account
 */
template::setTitle(lang::translate('account_request_password_title'));

$request = new request();
if (isset($_POST['submit'])){
    $mail_sent = $request->requestPassword($_POST['email']);
    if (empty($request->errors)){        
        if ($mail_sent){
            view_confirm (lang::translate('account_request_login_info_sent_to') . ": " . $_POST['email']);
        }
    } else {
        view_form_errors($request->errors);
        view_request();
    }
}

if (!isset($mail_sent)){
    view_request();
}