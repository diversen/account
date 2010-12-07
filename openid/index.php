<?php

/**
 * @ignore
 */
accountOpenid::init();
if (!accountOpenid::$loggedIn){
    accountOpenid::login();
    accountOpenid::viewLoginForm();    
} else {
    accountLogin::setId();
    accountLoginView::logout();
}