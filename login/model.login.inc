<?php

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
        //self::checkSystemCookie();

        if (@$_POST['keep_session'] == 'on'){
            $keep_session = 1;
        }

        $uri = uri::getInstance();
        if ($uri->fragment(3) == '1'){
            session::killSession();
            $redirect = get_module_ini('account_default_url');
            header( "Location: $redirect");
        }

        // login
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
                /*
                if (isset($_SESSION['redirect_on_login'])){
                    $redirect = $_SESSION['redirect_on_login'];
                    unset($_SESSION['redirect_on_login']);
                    header ("Location: $redirect");
                } else {
                    header('Location: /account/login/index');
                }*/
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
                $url = get_module_ini('account_default_url');
            }
            header("Location: $url");
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

    public static function controlLogin (){   
        accountLogin::login();
        if (session::isUser()){
            accountLoginView::logout();
        } elseif ( isset($_POST['submit']) && !accountLogin::$loggedIn ) {
            view_form_errors(lang::translate('account_login_wrong_username_password'));
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
        $password = md5($password);
        $search = array ('email' => $email, 'password' => $password);
        $row = $db->selectOne('account', null, $search);
        if (empty($row)){
            return 0;
        } else {
            return $row;
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