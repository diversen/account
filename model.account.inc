<?php

/**
 * File containing main account class
 */

template::setInlineCss(
        config::getModulePath('account') .  '/assets/account.css');

/**
 * class account. Only containing one method. 
 */
class account {
    
    public function checkLogout () {
        $logout = uri::getInstance()->fragment(3);
        if ($logout == '1'){
            session::killSession();
            $redirect = config::getModuleIni('account_default_url');
            if (isset($this->options['redirect_logout'])) {
                $redirect = $this->options['redirect_logout'];
            }
            http::locationHeader($redirect);
        }
    }
    
           /**
    * method for creating a logout link
    */
    public static function displayLogout(){
        $row = user::getAccount(session::getUserId()); 
        echo user::getLogoutHTML($row);
    }

    /**
     * Method for redireting to default login url. The default URL is set 
     * in the ini setting 'account_default_url'
     */
    public static function redirectDefault (){
        $redirect = config::getModuleIni('account_default_url');
        http::locationHeader ($redirect);
    }
    /*
    function redirectOnLogin() {
        if (isset($_SESSION['redirect_on_login'])){
            $redirect = $_SESSION['redirect_on_login'];
            unset($_SESSION['redirect_on_login']);
            header ("Location: $redirect");
        }
    }*/
   /**
     * sets self::$id from session
     */
    //public static function setId(){
        // logout is the same for all login methods so far.
        /*
        if (session::isUser()){
            self::$id = session::getUserId();
        }*/
    //}
    
    public static function redirectOnLogin ($url = null){
        if (isset($_SESSION['redirect_on_login'])){
            $redirect = $_SESSION['redirect_on_login'];
            unset($_SESSION['redirect_on_login']);
            http::locationHeader ($redirect);
        } else {
            if ($url){
                $location = $url;
            } else {
                $location = config::getModuleIni('account_default_url');
            }
            http::locationHeader($location);
         }
    }
}
