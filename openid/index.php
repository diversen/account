<?php

/**
 * @ignore
 */

template::setTitle(lang::translate('account_openid_login_index'));

// check to see if user is allowed to use faccebook login
if (!in_array('openid', config::getModuleIni('account_logins'))){
    moduleLoader::$status[403] = 1;
    return;
}

accountOpenid::init();
if (!accountOpenid::$loggedIn){
    accountOpenid::login();
    accountOpenid::viewLoginForm();    
} else {
    accountLogin::setId();
    accountLogin::displayLogout();
}