<?php

include_module('account');

class accountLogin extends account {

    /**
     * options
     * @var array $ary options 
     */
    public $options = array ();
    
    public function __construct($options = null) {
        $this->options = $options;
    } 
    
    /**
     * static method for doing a login
     */
    public function login ($options = null){


        // logout
        $this->checkLogout();
        

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
            }
        }
    }
    
    /**
     * method for displaying email login 
     */
    public static function displayLogin (){
        echo templateView::get('account', 'account_login_email');
    }

    /**
     *method for controlling email login 
     */
    public function controlLogin (){
        $this->login();
        if (session::isUser()){
            $this->displayLogout();
        } elseif ( isset($_POST['submit_account_login']) ) {
            view_form_errors(lang::translate('account_login_wrong_username_password'));
            $this->displayLogin();
        } else {
            $this->displayLogin();
        }
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
}
