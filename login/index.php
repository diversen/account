<?php

/**
 * @package    account
 */
// check to see if user is allowed to use faccebook login
if (!get_module_ini('use_email_login')){
    moduleLoader::$status[403] = 1;
    return;
}

// assign title to page
template::setTitle(lang::translate('Login or logout'));

accountLogin::controlLogin();