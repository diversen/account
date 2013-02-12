<?php

include_once 'facebook-php-sdk/src/facebook.php';
moduleloader::includeModule('account/login');
view::includeOverrideFunctions('account', 'facebook/views.phtml');

class accountFacebook extends account {

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
        if (isset($this->options['unique_email'])) {
            $account = $this->getUserFromEmail($user['email'], null);
            if (!empty($account)) {
                $auto_merge = config::getModuleIni('account_auto_merge');
                if ($auto_merge) {
                    return $this->autoMergeAccounts($user, $account['id']);                    
                }
                
                // if no auto merge we set an error
                $this->errors['facebook_email_exists'] = lang::translate('account: facebook: email already exists for user');
                return false;
            }
        }
        
        $md5_key = random::md5();   
        $values = array(
            'url'=> $user['link'], 
            'username' => strings::toUTF8($user['name']),
            'email' => $user['email'],
            'type' => 'facebook',
            'verified' => 1,
            'md5_key' => $md5_key
            );
        
        // If not isset options verified - we allow non verified account to log in
        if (isset($this->options['verified']) && !$this->options['verified']) {
            unset($values['verified']);
        }
        
        $res = $db->insert('account', $values);
        if ($res) {
            return db::$dbh->lastInsertId();
        }
        return $res;
    }
    
        /**
     * auto merge two accounts
     * @param objct $openid lightopenid object
     * @param int $user_id
     * @return int|false $parent_id main account id
     */
    public function autoMergeAccounts ($user, $user_id) {
        
        // examine if we are allowed to merge this URL
        $allow_merge = config::getModuleIni('account_auto_merge');
        $url = $user['link'];
        $parts = parse_url($url);
        $host = $parts['host'];
        $res = false;
        foreach($allow_merge as $host) {
            if (in_array($host, $allow_merge)) {
                $res = true;
                break;
            }
        }
        
        if ($res) {
            $res_create = $this->createUserSub($user, $user_id);
            if ($res_create) {
                return $user_id; 
            }
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
        
        // run account_create events
        $args = array (
            'action' => 'account_create',
            'user_id' => $id,
        );

        event::getTriggerEvent(
            config::getModuleIni('account_events'), 
            $args
        );
        
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
            'email' => $user['email'],
            'type' => 'facebook',
            'verified' => 1,
            'parent' => $user_id);
        
        // If not isset options verified - we allow non verified account to log in
        if (isset($this->options['verified']) && !$this->options['verified']) {
            unset($values['verified']);
        }
        
        if (isset($user['name'])) {
            $values['username'] = strings::toUTF8($user['name']);
        }
        
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

    public function login () {

        // check to see if user is allowed to use faccebook login
        $account_logins = config::getModuleIni('account_logins');
        if (!in_array('facebook', $account_logins)){
            moduleloader::setStatus(403);
            return;
        }
        
        if (session::isUser()) {   
            $this->displayLogout();
            return;
        }

        $facebook = $this->getFBObject();
        $user = $facebook->getUser();

        if ($user) {
            try {
                // Proceed knowing you have a logged in user who's authenticated.
                $user_profile = $facebook->api('/me');
                $logoutUrl = $facebook->getLogoutUrl(
                    array ('next' => 
                        accountFacebook::getLogoutNext()
                    )
                );
            } catch (FacebookApiException $e) {
                log::debug($e);
            }
        } else {
            $user_profile = null;
        }

        // login or logout url will be needed depending on current user state.
        if ($user_profile) {        
            $row = $this->auth($user_profile['link']);

            if (empty($row)){       
                $id = $this->createUser($user_profile);
            } else {
                $this->setSesisonAndCookie($row);
            }

            if (empty($this->errors)) {
                $this->redirectOnLogin ();
            } else {
                return false;
            }

        } else {
            $scope = $this->getScope();
            $loginUrl = $facebook->getLoginUrl(
                array(
                    'scope' => $scope,
                )
            );
        }

        // display login or logout -  you can override this in any template
        if ($user_profile) {
            account_facebook_logout_url ($logoutUrl);
        } else {
            account_facebook_login_url ($loginUrl);
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
     *method for getting scope for login
     * @return string 
     */
    public function getScope () {
        $scope = 'email,';
        $scope.= 'user_birthday,';
        $scope.= 'user_location,';
        $scope.= 'user_work_history,';
        $scope.= 'user_about_me,';
        $scope.= 'user_hometown,';
        $scope.= 'user_website';
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

function account_facebook_login () {

    // check to see if user is allowed to use faccebook login
    $account_logins = config::getModuleIni('account_logins');
    if (!in_array('facebook', $account_logins)){
        moduleloader::setStatus(403);
        return;
    }

    $account = new accountFacebook();
    $account->setAcceptUniqueOnlyEmail();
    if (session::isUser()) {   
        $account->displayLogout();
        return;
    }
    
    $facebook = facebook_get_object();
    $user = $facebook->getUser();

    if ($user) {
        try {
            // Proceed knowing you have a logged in user who's authenticated.
            $user_profile = $facebook->api('/me');
            $logoutUrl = $facebook->getLogoutUrl(
                array ('next' => 
                    accountFacebook::getLogoutNext()
                )
            );
        } catch (FacebookApiException $e) {
            log::debug($e);
        }
    } else {
        $user_profile = null;
    }

    // login or logout url will be needed depending on current user state.
    if ($user_profile) {        
        $row = $account->auth($user_profile['link']);

        if (empty($row)){       
            $id = $account->createUser($user_profile);
            if ($id) {
                $row = user::getAccount($id);
                $account->setSesisonAndCookie($row);
            }
        } else {
            $account->setSesisonAndCookie($row);
        }
        
        $account->redirectOnLogin ();

    } else {
        $scope = facebook_get_scope();
        $loginUrl = $facebook->getLoginUrl(
            array(
                'scope' => $scope,
            )
        );
    }

    // display login or logout -  you can override this in any template
    if ($user_profile) {
        account_facebook_logout_url ($logoutUrl);
    } else {
        account_facebook_login_url ($loginUrl);
    }
}

/**
 * try to login a user with facebook. No html will be displayed. Therefor
 * I consider it to be a service like function.
 * @return int $res positive if we have an authenticated user correct else 0
 *                  if already authenticated we return positive
 */
function account_facebook_service ($options = array ()) {

    // check to see if user is allowed to use faccebook login
    $account_logins = config::getModuleIni('account_logins');
    if (!in_array('facebook', $account_logins)){
        moduleloader::setStatus(403);
        return;
    }
    
    // return if already logged in. 
    if (session::isUser()) {
        return 1;
    }

    $facebook = facebook_get_object();   
    $user = $facebook->getUser();
    $user_profile = null;
    if ($user) {
        try {
            // $token = $facebook->getAccessToken();
            //print_r($token); die;
            // Proceed knowing you have a logged in user who's authenticated.
            $user_profile = $facebook->api('/me');
        } catch(FacebookApiException $e) {
            log::debug($e);
        }
    } 

    // login or logout url will be needed depending on current user state.
    if ($user_profile) {
        $account = new accountFacebook();
        $account->setAcceptUniqueOnlyEmail();
        $row = $account->auth($user_profile['link']);
 
        if (empty($account->errors)) {
            if  (empty($row)){   
                $res = $account->createUser($user_profile);
            } else {
                $res = $account->setSesisonAndCookie($row);
            }
        } 
        
        if (!$res) {

            //print_r($account->errors);
            session::setPersistentVar('facebook_error', $account->errors);
            $res = 0;

        }
       
    } else {
        $res = 0;
    }    
    return $res;
}


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
