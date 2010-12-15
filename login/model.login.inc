<?php

/**
 * File for holding login class
 * @package    account
 */

/**
 * class for doing logins
 * @package    account
 */
class accountLogin {

    /**
     * flag to see is a user is logged in or not
     * @var int     1 is user logged in 0 if not
     */
    public static $loggedIn = 0;

    public static $id;

    
    /**
     * static method for doing a login
     */
    public static function login (){
        //self::checkSystemCookie();

        if (@$_POST['keep_session'] == 'on'){
            //echo "we keep session";
            $keep_session = 1;
        }
        // logout is the same for all login methods so far.
        $uri = uri::getInstance();
        if ($uri->fragment(3) == '1'){
            session::killSession();
            $redirect = get_module_ini('account_default_url');
            header( "Location: $redirect");
            die;
        }

        if (isset($_POST['email']) && isset($_POST['password'])){
            $account = self::auth($_POST['email'], $_POST['password']);
            if ($account){

                $_SESSION['id'] = $account['id'];
                $_SESSION['admin'] = $account['admin'];
                $_SESSION['super'] = $account['super'];
                $_SESSION['account_type'] = 'email';

                if (isset($keep_session)){
                    session::setSystemCookie($account['id']);
                }

                self::redirectOnLogin();
                if (isset($_SESSION['redirect_on_login'])){
                    $redirect = $_SESSION['redirect_on_login'];
                    unset($_SESSION['redirect_on_login']);
                    header ("Location: $redirect");
                } else {
                    header('Location: /account/login/index');
                }
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
        } else {
            if ($url){
                $location = $url;
            } else {
                $url = get_module_ini('account_default_url');
            }
            header("Location: $url");
         }
    }

    public static function setId(){
        // logout is the same for all login methods so far.
        if (session::isUser()){
            self::$id = session::getUserId();
        }
    }

    public static function controlLogin (){
        
        accountLogin::login();
        if (session::isUser()){
            accountLoginView::logout();
        } elseif ( isset($_POST['submit']) && !accountLogin::$loggedIn ) {
            view_form_errors(lang::translate('Wrong email or password'));
            accountLoginView::login();
        } else {
            accountLoginView::login();
        }
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
        try {
            $password = md5($password);
            $search = array ('email' => $email, 'password' => $password);
            $row = $db->selectOne('account', null, $search);
            if (empty($row)){
                return 0;
            } else {
                return $row;
            }
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
    }
    /**
     *
     * @return mixed    user row or 0
     */
    public static function getUser(){
        $db = new db();
        $row = $db->selectOne('account', 'id', self::$id);
        if (!empty($row)){
            return $row;
        } 
        return 0;
    }
}