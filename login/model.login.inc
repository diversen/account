<?php

include_module('account');

class accountLogin extends account {

    /**
     * @var int     $loggedIn (1 yes 0 no)
     */
    public static $loggedIn = 0;

    /**
     *
     * @var int $id
     */
    public static $id;

    public $options = array ();
    
    /**
     * static method for doing a login
     */
    public function login ($options = null){
        
        // set options
        if (isset($options)) {
            $this->options = $options;
        }

        // logout
        $logout = uri::getInstance()->fragment(3);
        if ($logout == '1'){
            session::killSession();
            $redirect = config::getModuleIni('account_default_url');
            if (isset($this->options['redirect_logout'])) {
                $redirect = $this->options['redirect_logout'];
            }
            http::locationHeader($redirect);
        }

        // login
        if (isset($_POST['email']) && isset($_POST['password'])){
            $account = $this->auth ($_POST['email'], $_POST['password']);
            if (!empty($account)){

                $_SESSION['id'] = $account['id'];
                $_SESSION['admin'] = $account['admin'];
                $_SESSION['super'] = $account['super'];
                $_SESSION['account_type'] = 'email';

                if (isset($_POST['keep_session'])){
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
                

                $this->redirectOnLogin();
            } //else {
               // self::$loggedIn = 0;
          //  }
        }
        //$this->setId();
        
    }

    /*
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
    }*/ 

    
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
    public function controlLogin (){
        $this->login();
        if (session::isUser()){
            $this->displayLogout();
        } elseif ( isset($_POST['submit_account_login']) && !accountLogin::$loggedIn ) {
            view_form_errors(lang::translate('account_login_wrong_username_password'));
            $this->displayLogin();
        } else {
            $this->displayLogin();
        }
    }
    
       /**
    * method for creating a logout link
    */
    public static function displayLogout(){
        //account::setId();
        $row = user::getAccount(session::getUserId()); 
        ////accountLogin::getUser();  
        echo user::getLogoutHTML($row);
    }

    /**
     * method for authorizing a user
     *
     * @param   string  username
     * @param   string  password
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function auth ($email, $password){
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