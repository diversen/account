<?php

include_module('account');

class accountLogin extends account {

    /**
     * options
     * @var array $ary options 
     */
    //public $options = array ();
    
    /**
     * constructs accountLogin object.
     * set options 
     * @param array $options. Options can be:
     *              redirect on login: 'redirect' => '/path/to/redirect'
     *              accept accounts not verified: 'verified' => false  
     */
    public function __construct($options = null) {
        $this->options = $options;
    } 
    
    /**
     * static method for doing a login
     */
    public function login (){

        // logout
        $this->checkLogout();
        
        
        
        // login
        if (isset($_POST['email']) && isset($_POST['password']) /* && !empty(trim($_POST['password']))*/){
            $account = $this->auth ($_POST['email'], $_POST['password']);
            if (!empty($account)){
                
                $this->setSesisonAndCookie($account);               
                if ($this->options['redirect'] === false) {
                    return;
                }
                
                if (isset($this->options['redirect'])) {
                    $this->redirectOnLogin($this->options['redirect']);
                } else {
                    $this->redirectOnLogin();
                }       
            }
        }
    }
    

    
    /**
     * method for displaying email login 
     */
    public function displayLogin (){
        $vars['errors'] = $this->errors;        
        echo view::get('account', 'account_login_email', $vars);
    }

    /**
     * method for controlling email login 
     * 
     */
    public function controlLogin (){
        $this->login();
        if (session::isUser()){
            $this->displayLogout();           
        // submission has taking place but no redirect.     
        } elseif ( isset($_POST['submit_account_login']) ) {
            
            $this->errors[]= lang::translate('account_login_wrong_username_password');
            $this->displayLogin();
        // no submission
        } else {           
            $this->displayLogin();
        }     
    }
    
    /**
     * method for authorizing a user
     *
     * @param   string  $email
     * @param   string  $password
     * @param   array   $params 
     *                  array ('verified' => false', 'md5_password' => true) // if you don't require
     *                  the login creds to be verified. 
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function auth ($email, $password, $params = array ()){
        $db = new db();
        
        if (!isset($params['md5_password'])) {
            $password = md5(html::specialDecode($password));
        }
        $search = array (
            'email' => $email, 
            'password' => $password, 
            /*'type' => 'email', */ 
            'verified' => 1);

        // If not isset options verified - we allow non verified account to log in
        if (isset($this->options['verified']) && !$this->options['verified']) {
            unset($search['verified']);
        }
        
        $row = $db->selectOne('account', null, $search);
        return $row;
    }
    
    public function getUserFromEmail ($email) {
        $db = new db();
        return $db->selectOne('account', 'email', $email);
    }
}
