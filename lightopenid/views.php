<?php

class account_lightopenid_views {
    
    /**
     * displays jquery openid login options
     */
    public static function loginForm () {
        moduleloader::includeTemplateCommon('openid-selector');
        openid_selector_load_assets();
        echo openid_selector_get_form();
    }
}