<?php

moduleloader::includeModule('account');
view::includeOverrideFunctions('account', 'login/views.phtml');

class accountLogin extends account {
    
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
        if (isset($_POST['email']) && isset($_POST['password']) ){
            $account = $this->auth ($_POST['email'], $_POST['password']);
            if (!empty($account)){            
                $this->setSessionAndCookie($account);               
                if (@$this->options['redirect'] === false) {
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
        accountLoginViews::formLogin($vars);
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
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function auth ($email, $password = null){
        $row = $this->authGetAccount($email, $password);
        return $this->checkAccountFlags($row);
    }
    
    /**
     * get an account from password and email
     * @param string $email
     * @param string $password
     * @return array $row
     */
    public function authGetAccount ($email, $password) {
        $db = new db(); 
        
        $search = array (
            'email' => strings_mb::tolower($email), 
            'password' => md5($password), 
            'type' => 'email',
        );
               
        $row = $db->selectOne('account', null, $search);
        return $row;
    }
}
