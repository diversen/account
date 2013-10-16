<?php

/**
 * these can be overridden by template functions
 * place your own account_views in /templates/template/account/views.php
 */

class account_views {
    
    public static function getTermsLink () {
        if (!config::getModuleIni('account_hide_terms')) {
            return lang::translate('By registering, you agree to the <a href="/account/terms/privacy">privacy policy</a> and <a href="/account/terms/service">terms of service</a>.');
        }
    }
    
}