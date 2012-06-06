<?php

/**
 * File containing main account class
 */

template::setInlineCss(config::getModulePath('account') .  '/assets/account.css');

/**
 * class account. Only containing one method. 
 */
class account {

    /**
     * Method for redireting to default login url. The default URL is set 
     * in the ini setting 'account_default_url'
     */
    public static function redirectDefault (){
        $redirect = config::getModuleIni('account_default_url');
        header ("Location: $redirect");
        die;
    }
    
    function redirectOnLogin() {
        if (isset($_SESSION['redirect_on_login'])){
            $redirect = $_SESSION['redirect_on_login'];
            unset($_SESSION['redirect_on_login']);
            header ("Location: $redirect");
        }
    }
}
