<?php

include_module('account');

class accountLogin {

    /**
     * @var int     $loggedIn (1 yes 0 no)
     */
    public static $loggedIn = 0;

    /**
     *
     * @var int $id
     */
    public static $id;

    
    /**
     * static method for doing a login
     */
    public static function login (){        
        if (isset($_POST['keep_session'])){
            //die('keep_session');
            $keep_session = 1;
        }

        $uri = uri::getInstance();
        if ($uri->fragment(3) == '1'){
            session::killSession();
            $redirect = config::getModuleIni('account_default_url');
            header( "Location: $redirect");
        }

        // login
        if (isset($_POST['email']) && isset($_POST['password'])){
            $account = self::auth($_POST['email'], $_POST['password']);
            if (!empty($account)){

                $_SESSION['id'] = $account['id'];
                $_SESSION['admin'] = $account['admin'];
                $_SESSION['super'] = $account['super'];
                $_SESSION['account_type'] = 'email';

                if (isset($keep_session)){
                    session::setSystemCookie($account['id']);
                }
                
                $args = array (
                    'action' => 'account_login',
                    'user_id' => $account['id'],
                );
                
                event::getTriggerEvent(
                    config::getModuleIni('account_events'), 
                    $args
                );
                

                self::redirectOnLogin();
            } else {
                self::$loggedIn = 0;
            }
        }
        self::setId();
        
    }

    public static function redirectOnLogin ($url = null){
        if (isset($_SESSION['redirect_on_login'])){
            $redirect = $_SESSION['redirect_on_login'];
            unset($_SESSION['redirect_on_login']);
            header ("Location: $redirect");
            die;
        } else {
            if ($url){
                $location = $url;
            } else {
                $location = config::getModuleIni('account_default_url');
            }
            header("Location: $location");
         }
    }

    /**
     * sets self::$id from session
     */
    public static function setId(){
        // logout is the same for all login methods so far.
        if (session::isUser()){
            self::$id = session::getUserId();
        }
    }
    
    /**
     * method for displaying email login 
     */
    public static function displayLogin (){
        echo templateView::get('account', 'account_login_email');
        if (isset(self::$accountLoginHTML)){
            echo self::$accountLoginHTML;
        }
    }

    /**
     *method for controlling email login 
     */
    public static function controlLogin (){
        accountLogin::login();
        if (session::isUser()){
            accountLogin::displayLogout();
        } elseif ( isset($_POST['submit_account_login']) && !accountLogin::$loggedIn ) {
            view_form_errors(lang::translate('account_login_wrong_username_password'));
            accountLogin::displayLogin();
        } else {
            accountLogin::displayLogin();
        }
    }
    
       /**
    * method for creating a logout link
    */
    public static function displayLogout(){
        accountLogin::setId();
        $row = accountLogin::getUser();  
        echo user::getLogoutHTML($row);
    }

    /**
     * method for authorizing a user
     *
     * @param   string  username
     * @param   string  password
     * @return  array|0 row with user creds on success, 0 if not
     */
    public static function auth ($email, $password){
        $db = new db();
        $password = md5(html::specialDecode($password));
        $search = array ('email' => $email, 'password' => $password);
        $row = $db->selectOne('account', null, $search);
        return $row;
    }
    /**
     *
     * @return mixed    user row or 0
     */
    public static function getUser(){
        $db = new db();
        $row = $db->selectOne('account', 'id', self::$id);
        return $row;
    }
}