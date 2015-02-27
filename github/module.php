<?php

/**
 * contains class for logging in with github api
 * @package account
 */
use diversen\githubapi as githubApi;
use diversen\strings\mb as strings_mb;
use diversen\random;

moduleloader::includeModule('account');

/**
 * contains class for logging in with github api
 * @package account
 */
class account_github extends account {

    
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
        echo html::createLink($url, lang::translate('Github login'));
    }
    

    /**
     * method for controlling email login 
     * 
     */
    public function controlLogin (){
        if (session::isUser()){
            $this->displayLogout();  
        // submission has taking place but no redirect.     
        } else {
            $this->login();
            echo "<br /><br />" . account_views::getTermsLink();
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
        
        // check if user exists in db
        if (isset($res['id']) && !empty($res['id'])) {
            
            // generate user we search for
            $db = new db();
            $search = array (
                'type' => 'github',
                'url' => $res['id'],
                'email' => strings_mb::tolower($res['email'])
            );
            
            
            $account = $this->githubAccountExist($search);
           
            
            // account exists - login
            if (!empty($account)) {  
                $this->doLogin ($account);                
            }
            

            // New account
            // Check if we use unique email only - one account per user
            if (isset($this->options['unique_email'])) {
                $account = $this->getUserFromEmail($search['email'], null);

                if (!empty($account)) {

                    $res = $this->autoMergeAccounts($search, $account['id']);
                    $this->doLogin ($account);   
                    
                }
            } else {
            
                // we allow more accounts per user
                $res = $db->insert('account', $search);
                if (!$res) { 
                    die('Could not create account');
                }
                $last_insert_id = $db->lastInsertId();
                $account = user::getAccount($last_insert_id);
                return $this->doLogin($account);
            }
            
        }
    }
    
    /**
     * sets session and cookie
     * @param array $account
     * @return boolean $res
     */
    public function doLogin ($account) {
        $this->setSessionAndCookie($account);               
        $this->redirectOnLogin();
    }
    
        /**
     * method for authorizing a user
     *
     * @param   string  username
     * @param   string  password
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function githubAccountExist ($params){
        
        // first check for a sub account and return parent account
        $db = new db();
        $search = array ('url' => $params['url'], 'type' => 'github');
        $row = $db->selectOne('account_sub', null, $search);
        if (!empty($row)) {
            $row = $db->selectOne('account', null, array ('id' => $row['parent']));
            $row = $this->checkAccountFlags($row);
            return $row;
        } 
        
        // check main account
        $search = array ('url' => $params['url'], 'type' => 'github');
        $row = $db->selectOne('account', null, $search);
        $row = $this->checkAccountFlags($row);
        return $row;
    }
    
    /**
     * auto merge two accounts
     * @param objct $openid lightopenid object
     * @param int $user_id
     * @return int|false $parent_id main account id
     */
    public function autoMergeAccounts($search, $user_id) {

        $res_create = $this->createUserSub($search, $user_id);
        if ($res_create) {

            // run account_connect events
            $args = array(
                'action' => 'account_connect',
                'user_id' => $user_id,
            );

            event::getTriggerEvent(
                    config::getModuleIni('account_events'), $args
            );

            return $user_id;
        }

        return false;
    }

    /**
     * method for creating a sub user
     *
     * @return int|false $res last_isnert_id on success or false on failure
     */
    public function createUserSub ($search, $user_id){
        
        $db = new db();
        $values = array(
            'url'=> $search['url'], 
            'email' => strings_mb::tolower($search['email']),
            'type' => 'github',
            'verified' => 1,
            'parent' => $user_id);
                
        $res = $db->insert('account_sub', $values);
        if ($res) {
            return $db->lastInsertId();
        }
        return $res;
    }
}
