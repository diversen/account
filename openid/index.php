<?php

/**
 * @ignore
 */

template::setTitle(lang::translate('account_openid_login_index'));

// check to see if user is allowed to use faccebook login
if (!config::getModuleIni('account_use_openid_login')){
    moduleLoader::$status[403] = 1;
    return;
}

accountOpenid::init();
if (!accountOpenid::$loggedIn){
    accountOpenid::login();
    accountOpenid::viewLoginForm();    
} else {
    accountLogin::setId();
    accountLoginView::logout();
}