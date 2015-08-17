<?php

namespace modules\account\facebook;

use diversen\conf;
use diversen\db;
use diversen\html;
use diversen\lang;
use diversen\log;
use diversen\moduleloader;
use diversen\random;
use diversen\session;
use diversen\strings;
use diversen\strings\mb;
use diversen\template;
use diversen\user;
use diversen\view;

// Facebook
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookSession;
use Facebook\GraphUser;

moduleloader::includeModule('account/login');
view::includeOverrideFunctions('account', 'facebook/views.php');

use modules\account\module as account;
use modules\account\facebook\views as account_facebook_views;
use modules\account\views as account_views;

class module extends account {

    /**
     * index action
     */
    public function indexAction() {

        // check to see if user is allowed to use faccebook login
        $account_logins = conf::getModuleIni('account_logins');
        if (!in_array('facebook', $account_logins)){
            moduleloader::setStatus(403);
            return;
        }
        
        usleep(100000);
        template::setTitle(lang::translate('Facebook Login'));
        $this->options['keep_session'] = 1;
        $this->login();
        if (!empty($this->errors)) {
            echo html::getErrors($this->errors);
        }
    }

    /**
     * method for authorizing a user
     * @param   string  $facebook_url
     * @return  array   $row with user creds on success, empty if no user
     */

    public function auth ($facebook_url){
     
        // first check for a sub account and return parent account
        $db = new db();
        $search = array ('url' => $facebook_url, 'type' => 'facebook');
        $row = $db->selectOne('account_sub', null, $search);
        if (!empty($row)) { 
            $row = $db->selectOne('account', null, array ('id' => $row['parent']));
            $row = $this->checkLocked($row);
            return $row;
        } 
        
        // check main account
        $row = $db->selectOne('account', null, $search);      
        $row = $this->checkLocked($row);        
        return $row;
        
    }

    /**
     * method for creating a user in the database
     * @param array $ary facebook user array
     * @return int|false $res last insert id or false if failure
     */
    public function createDbUser ($user_profile){
        
        $db = new db();
       
        // check if we allow to merge an account based on email match
        // if we did not get facebook account email - we always create
        // a new account
        $email = $user_profile->getEmail();
        
        // empty account array
        $account = array ();
        if (!empty($email)) {
            
            // check if an account exists with this email
            $account = $this->getUserFromEmail($email);
            
            // check if account based on email is locked
            $this->checkLocked($account);
            if (!empty($this->errors)) {
                echo html::getErrors($this->errors);
                return false;
            }
        } 
        
        // not locked and an account exists - we merge accounts
        if (!empty($account)) {
            return $this->autoMergeAccounts($user_profile, $account['id']);                    
        }

        // new account
        $md5_key = random::md5();   
        $values = array(
            'url'=> $user_profile->getLink(), 
            'username' => strings::toUTF8($user_profile->getName()),
            'email' => mb::tolower($user_profile->getEmail()),
            'type' => 'facebook',
            'verified' => 1,
            'md5_key' => $md5_key
            );
        
        
        $res = $db->insert('account', $values);
        if ($res) {           
            $id = $db->lastInsertId();
            
            // run account_create events
            $args = array (
                'action' => 'account_create',
                'type' => 'facebook',
                'user_id' => $id,
            );

            event::getTriggerEvent(
                conf::getModuleIni('account_events'), 
                $args
            );
            
            return $id;
            
        }
        return $res;
    }
    
    /**
     * auto merge two accounts
     * @param 
     * @param int $user_id
     * @return int|false $parent_id main account id
     */
    public function autoMergeAccounts($user, $user_id) {

        $res_create = $this->createUserSub($user, $user_id);
        if ($res_create) {

            // run account_connect events
            $args = array(
                'action' => 'account_connect',
                'type' => 'facebook',
                'user_id' => $user_id,
            );

            event::getTriggerEvent(
                    conf::getModuleIni('account_events'), $args
            );

            return $user_id;
        }

        return false;
    }

    /**
     * creates a facebook user from facebook profile array
     * 
     * account_events::account_create
     * 
     * @param array $user_profile 
     */
    public function createUser ($user_profile) {
        
        // we have a facebook session but no user in database
        $id = $this->createDbUser($user_profile);
        if (!$id) {
            return false;
        }
        
        // run account_login events
        $login_args = array (
            'action' => 'account_login',
            'user_id' => $id,
        );

        event::getTriggerEvent(
            conf::getModuleIni('account_events'), 
            $login_args
        );
        
        return $id;
    }
    
    /**
     * method for creating a sub user
     *
     * @return int|false $res last_isnert_id on success or false on failure
     */
    public function createUserSub ($user, $user_id){
        
        $db = new db();
        $row = $db->selectOne('account_sub', 'url', $user->getLink());
        if (!empty($row)) {
            return array ();
        }
        
        $values = array(
            'url'=> $user->getLink(),
            'email' => mb::tolower($user->getEmail()),
            'type' => 'facebook',
            'verified' => 1,
            'parent' => $user_id);

        
        $res = $db->insert('account_sub', $values);
        if ($res) {
            return $db->lastInsertId();
        }
        return $res;
    }
    

    
    /**
     * method for logging in a user. Used as a display function. 
     * means that it will draw login and logout links at the same time
     * as users are authenticated. 
     */

    public function login($scope = null) {

        // if we already have user - display logut
        if (session::isUser()) {
            $this->displayLogout();
            return;
        }

        $redirect_url = conf::getSchemeWithServerName() . "/account/facebook/index";
        $app_id = conf::getModuleIni('account_facebook_api_appid');
        $app_secret = conf::getModuleIni('account_facebook_api_secret');
        FacebookSession::setDefaultApplication($app_id , $app_secret);
        $helper = new FacebookRedirectLoginHelper($redirect_url);
                
        try {
            $session = $helper->getSessionFromRedirect();
        } catch (FacebookRequestException $e) {
            log::error($e->getMessage());
            return false;
        } catch (\Exception $e) {
            log::error($e->getMessage());
            return false;
        }

        // $test = new FacebookSession();
       
        $user_profile = null;
        if ($session) { //if we have the FB session
            try {
                // get user profile
                $user_profile = (
                        new FacebookRequest($session, 'GET', '/me'))->
                        execute()->
                        getGraphObject(GraphUser::className()
                );
            } catch (\Exception $e) {
                echo($e->getMessage());
                log::error($e->getMessage());
                return false;
            }

            // check config to see if we require an email
            // set account_no_email = true if you will facebook logins without email
            $account_no_email = conf::getModuleIni('account_no_email');
            if (!$account_no_email && empty($user_profile->getEmail())) {
                $this->errors[] = lang::translate('We will need your email. No login without email. Please try again!');
                $request = new FacebookRequest(
                    $session,
                    'DELETE',
                    '/me/permissions'
                );
                $response = $request->execute();
                $graphObject = $response->getGraphObject();
                
                return false;
            }

            // does user exists and is he already registered
            $row = $this->auth($user_profile->getLink());
            if (!empty($this->errors)) {
                return false;
            }

            // no errors - new user - create 
            if (empty($row)){       
                $id = $this->createUser($user_profile);
                $row = user::getAccount($id);
            }

            // set session and cookie
            $this->setSessionAndCookie($row);
            if (empty($this->errors)) {
                $this->redirectOnLogin ();
            } else {
                return false;
            }
        } else {
            //display login url
            $scope =$this->getScope();
            $login_url = $helper->getLoginUrl(array('scope' => $scope));
            account_facebook_views::loginLink ($login_url);
            echo "<br /><br />" . account_views::getTermsLink();
        }
        return;
    }

    /**
     * method for getting the facebook 'next' param - the url to
     * redirect to when logging out. 
     * @return string $str the logout url
     */
    public static function getLogoutNext () {
        $server = conf::getMainIni('server_name');
        return "http://$server/account/facebook/logout";
    }
    


    /**
     * method for getting scope for login. If there is not set a scope
     * in account_facebook_scope then scope defaults to 'email'
     * @return string 
     */
    public function getScope () {
        $scope = conf::getModuleIni('account_facebook_scope');
        if (!$scope) {       
            $scope = 'email';
        }
        return $scope;
    }
    
    
    /**
     * dunmmy function for displaying docs about events
     * 
     * account_events (set in account_events)
     * 
     * method createUser fires actions:
     * 
     *      account_create 
     *      account_login
     * 
     * arguments to the event methods is
     *      action,
     *      user_id,
     * 
     */
    public static function __events () {}
}
