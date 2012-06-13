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

$l = new accountLightopenid();
$l->init();
if (!$l->loggedIn){
    $l->login();
    $l->viewLoginForm();    
} else {
    accountLogin::setId();
    accountLogin::displayLogout();
}
