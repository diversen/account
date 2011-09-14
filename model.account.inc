<?php

template::setModuleInlineCss('account', 'assets/account.css');

class account {

    public static function redirectDefault (){
        $redirect = get_module_ini('account_default_url');
        header ("Location: $redirect");
        die;
    }
}