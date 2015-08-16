<?php
namespace modules\account\facebook;

use diversen\html;
use diversen\lang;

class views {
    /**
     * echo a facebook login url
     * @param string $loginUrl facebook login url.
    */
    public static function loginLink ($loginUrl) {
        echo html::createLink($loginUrl, lang::translate('Login using facebook'));
    }
    
    /**
     * echo a facebook logout url
     * @param string $loginUrl facebook logout url.
    */
    public static function logoutLink($logoutUrl) {
        echo html::createLink($logoutUrl, lang::translate('Logout'));
    }
}
