<?php

usleep(100000);
template::setTitle(lang::translate('account_openid_login_index'));

// check to see if user is allowed to use lightopenid
if (!in_array('lightopenid', config::getModuleIni('account_logins'))){
    moduleloader::setStatus(403);
    return;
}

$options = array ();
if (isset($_GET['keep_session']) && $_GET['keep_session'] == 1) {
    $_SESSION['keep_session'] = 1;
}

if (isset($_SESSION['keep_session'])) {
    $options['keep_session'] = 1;
}

$options['unique_email'] = true;
$l = new account_lightopenid($options);
if (!session::isUser()){
    $l->login();
    if (!empty($l->status)) {
        echo $l->status;
    } 
    if (!empty($l->errors)) {
        html::errors($l->errors);
    }
    $l->viewLoginForm();    
} else {
    $a = new account_login();
    $a->displayLogout();
}
