<?php

use diversen\conf;
use diversen\lang;

// get types of logins
$account_logins = conf::getModuleIni('account_logins');

$_MODULE_MENU = array();



$_MODULE_MENU[] = array(
    'title' => lang::translate('Admin'),
    'url' => '/account/admin/list',
    'auth' => 'admin');

/**
 * anon function which return a menu item for each account type enabled
 */
$attach_menu = function ($type) { 
    if ($type == 'email') {
        return array(
            'title' => lang::translate('Email'),
            'url' => '/account/login/index'
        );
    }
    
    if ($type == 'lightopenid') {
        return array(
            'title' => lang::translate('OpenID'),
            'url' => '/account/lightopenid/index'
        );
    }
    
    if ($type == 'facebook'){
        return array(
            'title' => lang::translate('Facebook'),
            'url' => '/account/facebook/index'
        );
    }
    
    if ($type == 'github'){
        return array(
            'title' => lang::translate('Github'),
            'url' => '/account/github/index'
        );
    }
    
    if ($type == 'google'){
        return array(
            'title' => lang::translate('Google'),
            'url' => '/account/google/index'
        );
    }
};

foreach($account_logins as $login) {
    $_MODULE_MENU[] = $attach_menu($login);
}
