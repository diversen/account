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

$account = new accountLightopenid();
$account->init();
if (!$account->loggedIn){
    $account->login();
    $account->viewLoginForm();    
} else {
    accountLogin::setId();
    accountLoginView::logout();
}
die;