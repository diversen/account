<?php

use diversen\strings;
use diversen\strings\mb as strings_mb;
use diversen\random;

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphUser;
use Facebook\FacebookRequestException;
use Facebook\FacebookRedirectLoginHelper;

//define('FACEBOOK_SDK_V4_SRC_DIR', _COS_PATH . 'vendor/facebook/php-sdk-v4/src/');
//require_once _COS_PATH . "/vendor/facebook/php-sdk-v4/autoload.php";



moduleloader::includeModule('account/login');
view::includeOverrideFunctions('account', 'facebook/views.php');

class account_facebook extends account {

    /**
     * index action
     */
    public function indexAction() {

        // check to see if user is allowed to use faccebook login
        $account_logins = config::getModuleIni('account_logins');
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
        $account = $this->getUserFromEmail($user_profile->getEmail());
        $this->checkLocked($account);
        if (!empty($this->errors)) {
            echo html::getErrors($this->errors);
            return;
        }
        
        if (!empty($account)) {
            return $this->autoMergeAccounts($user_profile, $account['id']);                    
        }

        
        $md5_key = random::md5();   
        $values = array(
            'url'=> $user_profile->getLink(), 
            'username' => strings::toUTF8($user_profile->getName()),
            'email' => strings_mb::tolower($user_profile->getEmail()),
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
                config::getModuleIni('account_events'), 
                $args
            );
            
            return $id;
            
        }
        return $res;
    }
    
    /**
     * auto merge two accounts
     * @param objct $openid lightopenid object
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
                    config::getModuleIni('account_events'), $args
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
            config::getModuleIni('account_events'), 
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
            'email' => strings_mb::tolower($user->getEmail()),
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

        $redirect_url = config::getSchemeWithServerName() . "/account/facebook/index";
        $app_id = config::getModuleIni('account_facebook_api_appid');
        $app_secret = config::getModuleIni('account_facebook_api_secret');
        FacebookSession::setDefaultApplication($app_id , $app_secret);
        $helper = new FacebookRedirectLoginHelper($redirect_url);
                
        try {
            $session = $helper->getSessionFromRedirect();
        } catch (FacebookRequestException $ex) {
            die(" Error : " . $ex->getMessage());
        } catch (\Exception $ex) {
            die(" Error : " . $ex->getMessage());
        }

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

            // we need email
            /*
            if (empty($user_profile->getEmail())) {
                $this->errors[] = lang::translate('We will need your email. No login without email');
                return false;
            }*/
            
            // auth if user exists
            $row = $this->auth($user_profile->getLink());
            if (!empty($this->errors)) {
                return false;
            }
            
            // new user - create row
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
        $server = config::getMainIni('server_name');
        return "http://$server/account/facebook/logout";
    }
    


    /**
     * method for getting scope for login. If there is not set a scope
     * in account_facebook_scope then scope defaults to 'email'
     * @return string 
     */
    public function getScope () {
        $scope = config::getModuleIni('account_facebook_scope');
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
