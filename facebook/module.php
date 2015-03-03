<?php

use diversen\strings;
use diversen\strings\mb as strings_mb;
use diversen\random;

moduleloader::includeModule('account/login');
view::includeOverrideFunctions('account', 'facebook/views.php');

class account_facebook extends account {

    /**
    public function __construct($options = array()) {
        parent::__construct($options);
    }
     * 
     */
    
    /**
     * index action
     */
    public function indexAction() {
        usleep(100000);
        template::setTitle(lang::translate('Facebook Login'));
        
        $this->options['keep_session'] = 1;// = $options;
        $this->login();

    }

    /**
     * method for authorizing a user
     * @param   string  $facebook_url
     * @return  array   $row with user creds on success, empty if no user
     */

    public function auth ($profile){
     
        // check if email is set and if user can log in
        $row = $this->getUserFromEmail($profile['email']);
        $row = $this->checkAccountFlags($row);
        if (empty($row)) {
            return $row;
        }
        
        
        // first check for a sub account and return parent account
        $db = new db();
        $search = array ('url' => $profile['link'], 'type' => 'facebook');
        $row = $db->selectOne('account_sub', null, $search);
        if (!empty($row)) { 
            $row = $db->selectOne('account', null, array ('id' => $row['parent']));
            $row = $this->checkAccountFlags($row);
            return $row;
        } 
        
        // check main account
        $row = $db->selectOne('account', null, $search);      
        $row = $this->checkAccountFlags($row);        
        return $row;
        
    }

    /**
     * method for creating a user in the database
     * @param array $ary facebook user array
     * @return int|false $res last insert id or false if failure
     */
    public function createDbUser ($user){
         
        $db = new db();

        print_r($user);
        $account = $this->getUserFromEmail($user['email'], 'email');
        $this->checkAccountFlags($account);
        if (!empty($this->errors)) {
            echo html::getErrors($this->errors);
            return;
        }
        
        print_r($account); die;
        
        if (!empty($account)) {
            return $this->autoMergeAccounts($user, $account['id']);                    
        }

        
        $md5_key = random::md5();   
        $values = array(
            'url'=> $user['link'], 
            'username' => strings::toUTF8($user['name']),
            'email' => strings_mb::tolower($user['email']),
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
        $row = $db->selectOne('account_sub', 'url', $user['link']);
        if (!empty($row)) {
            return array ();
        }
        
        $values = array(
            'url'=> $user['link'],
            'email' => strings_mb::tolower($user['email']),
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

    public function login ($scope = null) {

        // check to see if user is allowed to use faccebook login
        $account_logins = config::getModuleIni('account_logins');
        if (!in_array('facebook', $account_logins)){
            moduleloader::setStatus(403);
            return;
        }
        
        // if we already have user - display logut
        if (session::isUser()) {   
            $this->displayLogout();
            return;
        }

        
        $facebook = $this->getFBObject();
        
        // get user id 
        $user = $facebook->getUser();
        if ($user) {
            try {
                // Proceed knowing you have a logged in user who's authenticated.
                // create a logout link
                $user_profile = $facebook->api('/me');
                $logoutUrl = $facebook->getLogoutUrl(
                    array ('next' => 
                        self::getLogoutNext()
                    )
                );
            } catch (FacebookApiException $e) {
                log::debug($e->getMessage());
            }
        } else {
            $user_profile = null;
        }

        // login or logout url will be needed depending on current user state.
        if ($user_profile) {        
  
            $row = $this->auth($user_profile);
            if (!empty($this->errors)) {
                echo html::getError($this->errors);
                return false;
            }
            
            
            
            // new user - create row
            if (empty($row)){       
                $id = $this->createUser($user_profile);
                $row = user::getAccount($id);
            }

            $this->setSessionAndCookie($row);
            if (empty($this->errors)) {
                $this->redirectOnLogin ();
            } else {
                return false;
            }

        } else {
            if (!$scope) {
                $scope = $this->getScope();
            }
            $loginUrl = $facebook->getLoginUrl(
                array(
                    'scope' => $scope,
                )
            );
        }

        // display login or logout -  you can override this in any template
        if ($user_profile) {
            account_facebook_views::logoutLink ($logoutUrl);
        } else {
            account_facebook_views::loginLink ($loginUrl);
            echo "<br /><br />" . account_views::getTermsLink();
        }
    }
    
    
    /**
     * create a facebook object
     * @return object $obj facebook object
     */
    function getFBObject () {
        static $facebook = null;
        if (!$facebook)
            $facebook = new Facebook(array(
                'appId'  => config::getModuleIni('account_facebook_api_appid'),
                'secret' => config::getModuleIni('account_facebook_api_secret'),
                'cookie' => true,  
            ));
        return $facebook;
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



/**
 * method for logging in a user. Used as a display function. 
 * means that it will draw login and logout links at the same time
 * as users are authenticated. 
 */

/**
 * create a facebook object
 * @return object $obj facebook object
 */
function facebook_get_object () {
    static $facebook = null;
    if (!$facebook)
        $facebook = new Facebook(array(
            'appId'  => config::getModuleIni('account_facebook_api_appid'),
            'secret' => config::getModuleIni('account_facebook_api_secret'),
            'cookie' => true,
        ));
    return $facebook;
}

/**
 * method for getting a user profile
 * @return array $ary user profile
 */
function facebook_get_user_profile () {
    
    $facebook = facebook_get_object();
    $user = $facebook->getUser();
    if ($user) {
        try {
            // Proceed knowing you have a logged in user who's authenticated.
            $user_profile = $facebook->api('/me');
        } catch (FacebookApiException $e) {
            log::debug($e);
        }
    } else {
        $user_profile = null;
    }
    return $user_profile;
}

/**
 * method for getting login url
 * @param type $options
 * @return string $str login url as html
 */
function facebook_get_login_url ($options = array ()) {

    $facebook = facebook_get_object();
    $scope = facebook_get_scope();
    $ary = 
        array(
            'scope' => $scope,
        );
    $ary+=$options;    
    return $loginUrl = $facebook->getLoginUrl($ary);
}

/**
 *method for getting scope for login
 * @return string 
 */
function facebook_get_scope () {
    $scope = 'email,';
    $scope.= 'user_birthday,';
    $scope.= 'user_location,';
    $scope.= 'user_work_history,';
    $scope.= 'user_about_me,';
    $scope.= 'user_hometown,';
    $scope.= 'user_website';
    return $scope;
}
