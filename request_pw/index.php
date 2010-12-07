<?php

/**
 * @package    account
 */
template::setTitle(lang::translate('Request Password'));

$request = new request();
if (isset($_POST['submit'])){
    $mail_sent = $request->requestPassword($_POST['email']);
    if (empty($request->errors)){        
        if ($mail_sent){
            view_confirm (lang::translate("Login info sent to") . ": " . $_POST['email']);
        }
    } else {
        view_form_errors($request->errors);
        view_request();
    }
}

if (!isset($mail_sent)){
    view_request();
}