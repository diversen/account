<?php

include_once 'facebook-php-sdk/src/facebook.php';
moduleLoader::includeModule('account/login');

class accountFacebook extends account {

    /**
     * method for getting the facebook 'next' param - the url to
     * redirect to when logging out. 
     * @return string $str the logout url
     */
    public static function getLogoutNext () {
        $server = get_main_ini('server_name');
        return "http://$server/account/facebook/logout";
    }


    /**
     * method for authorizing a user
     * @param   string  $facebook_url
     * @return  array   $row with user creds on success, empty if no user
     */

    public function auth ($facebook_url){
        /*
        $db = new db();
        $search = array ('url' => $facebook_url, 'type' => 'facebook');
        $row = $db->selectOne('account', null, $search);
        return $row;
        */
        // first check for a sub account and return parent account
        $db = new db();
        $search = array ('url' => $facebook_url, 'type' => 'facebook');
        $row = $db->selectOne('account_sub', null, $search);
        if (!empty($row)) {
            $row = $db->selectOne('account', null, array ('id' => $row['parent']));
            return $row;
        } 
        
        // check main account
        //$search = array ('url' => $openid, 'type' => 'openid');
        $row = $db->selectOne('account', null, $search);
        return $row;
        
        
    }

    /**
     * method for creating a user in the database
     * @param array $ary facebook user array
     * @return int|false $res last insert id or false if failure
     */
    public function createDbUser ($user){
        $db = new db();     
        $values = array(
            'url'=> $user['link'], 
            'username' => strings::toUTF8($user['name']),
            'email' => $user['email'],
            'type' => 'facebook',
            'verified' => 1
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
     * creates a facebook user from facebook profile array
     * 
     * account_events::account_create
     * 
     * @param array $user_profile 
     */
    function createUser ($user_profile) {
        // we have a facebook session but no user in database
        $id = $this->createDbUser($user_profile);
        $_SESSION['id'] = $id;
        $_SESSION['account_type'] = 'facebook';

        $args = array (
            'action' => 'account_create',
            'user_id' => $id,
        );

        event::getTriggerEvent(
            config::getModuleIni('account_events'), 
            $args
        );
        
        $login_args = array (
            'action' => 'account_login',
            'user_id' => $id,
        );

        event::getTriggerEvent(
            config::getModuleIni('account_events'), 
            $login_args
        );
        
        
    }
    
    
    /**
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
        moduleLoader::setStatus(403);
        return;
    }

    $account = new accountFacebook();    
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
            cos_debug($e);
        }
    } else {
        $user_profile = null;
    }

    // login or logout url will be needed depending on current user state.
    if ($user_profile) {        
        $row = $account->auth($user_profile['link']);

        if (empty($row)){       
            $account->createUser($user_profile);
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

    // display login or logout
    if ($user_profile) { 
        echo html::createLink($logoutUrl, 'Log Out');
    } else {
        echo html::createLink($loginUrl, 'Log in');
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
        moduleLoader::setStatus(403);
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
            cos_debug($e);
        }
    } 

    // login or logout url will be needed depending on current user state.
    if ($user_profile) {
        $account = new accountFacebook();
        $row = $account->auth($user_profile['link']);

        if (empty($row)){   
            $res = $account->createUser($user_profile);
        } else {
            $res = $account->setSesisonAndCookie($row);
        }
       
    } else {
        //session::killSession();
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
            cos_debug($e);
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
