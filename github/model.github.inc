<?php

use \diversen\githubapi as githubApi;
moduleloader::includeModule('account');
//include_once "coslib/githubApi.php";
//include_once "coslib/mycurl.php";

class accountGithub extends account {

    
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
        
        $callback = config::getSchemeWithServerName() . "/account/github/callback";  
        $access_config = array (
            'redirect_uri' => $callback,
            'client_id' => config::getModuleIni('account_github_id'),
            'state' =>  random::md5(),
        );
        
        $scope = config::getModuleIni('account_github_scope');
        if ($scope) $access_config['scope'] = $scope;

        // login
        $api = new githubApi();
        $url = $api->getAccessUrl($access_config);
        echo html::createLink($url, lang::translate('account: github: Login'));
    }
    

    /**
     * method for controlling email login 
     * 
     */
    public function controlLogin (){
        //$this->login();
        if (session::isUser()){
            $this->displayLogout();    
        // submission has taking place but no redirect.     
        } else {
            $this->login();
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
    public function auth (){
        $api = new githubApi();
        $res = $api->apiCall('/user');

        // user id is unique - we use this as 'url' which is unique
        $res['id'] = (int)$res['id'];

        if (isset($res['id']) && !empty($res['id'])) {
            $db = new db();
            $search = array (
                'type' => 'github',
                'url' => $res['id'],
                'email' => strings_mb::tolower($res['email'])
                );
            $account = $db->selectOne('account', null, $search);
            if (empty($account)) {
                $res = $db->insert('account', $search);
                if (!$res) die('Could not create account');
                $last_insert_id = $db->lastInsertId();
                $account = user::getAccount($last_insert_id);
            }

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
