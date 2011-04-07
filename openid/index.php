<?php

/**
 * @ignore
 */

template::setTitle(lang::translate('openid_login'));

// check to see if user is allowed to use faccebook login
if (!get_module_ini('account_use_openid_login')){
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