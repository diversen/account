<?php

/**
 * @ignore
 */

template::setTitle(lang::translate('account_openid_login_index'));

// check to see if user is allowed to use faccebook login
if (!in_array('lightopenid', config::getModuleIni('account_logins'))){
    moduleLoader::setStatus(403);
    return;
}

// $options = array ('openid_identifier' => 'https://www.google.com/accounts/o8/id');
// $l = new accountLightopenid($options);
$l = new accountLightopenid();
if (!session::isUser()){
    $l->login();
    if (!empty($l->status)) {
        echo $l->status;
    } 
    if (!empty($l->errors)) {
        view_form_errors($l->errors);
    }
    $l->viewLoginForm();    
} else {
    $a = new accountLogin();
    $a->displayLogout();
}
