<?php

/**
 * File containing main account class
 */

template::setInlineCss(get_module_path('account') .  '/assets/account.css');

/**
 * class account. Only containing one method. 
 */
class account {

    /**
     * Method for redireting to default login url. The default URL is set 
     * in the ini setting 'account_default_url'
     */
    public static function redirectDefault (){
        $redirect = get_module_ini('account_default_url');
        header ("Location: $redirect");
        die;
    }
}