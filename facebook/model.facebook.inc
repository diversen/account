<?php

include_once 'facebook.php';
moduleLoader::includeModule('account/login');

class accountFacebook {
    /**
     *
     * @var boolean $loggedIn tells if user is logged in or not
     */
    public static $loggedIn = null;

    /**
     *
     * @var int $id the users id. 
     */
    public static $id;

    /**
     *
     * @var array $errors array holding errors
     */
    public static $errors = null;

    /**
     * init facebook
     * sets static vars logginIn and userId
     * checks if user is logged in
     * checks if session needs to be killed (logging out)
     * @return boolean $res true if user is logged in else false
     */
    public static function  init() {
        // logout is the same for all login methods so far.
        $uri = uri::getInstance();
        if ($uri->fragment(3) == '1'){
            session_destroy();
            return false;
        }
        
        if (session::isUser()){
            self::$loggedIn = 1;
            self::$id = session::getUserId();
            return true;
        }
    }
    
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
        $db = new db();
        $search = array ('url' => $facebook_url);
        $row = $db->selectOne('account', null, $search);
        return $row;
    }

    /**
     * method for creating a user
     * @return int $res last insert id or false if failure
     */
    public function createDbUser ($user){
        $db = new db();     
        $values = array(
            'url'=> $user['link'], 
            'username' => $user['name'],
            'email' => $user['email']
            );
        $res = $db->insert('account', $values);
        if ($res) {
            return db::$dbh->lastInsertId();
        }
        return $res;
    }
    
    function createUser () {
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

        $args = array (
            'action' => 'account_login',
            'user_id' => $id,
        );

        event::getTriggerEvent(
            config::getModuleIni('account_events'), 
            $args
        );
    }
    
    function loginUser ($row) {
        // we have a row - user exists - we set creds
        $_SESSION['id'] = $row['id'];
        $_SESSION['admin'] = $row['admin'];
        $_SESSION['super'] = $row['super'];
        $_SESSION['account_type'] = 'facebook';

        $args = array (
            'action' => 'account_login',
            'user_id' => $row['id'],
        );

        event::getTriggerEvent(
            config::getModuleIni('account_events'), 
            $args
        );
    }

    /**
     *
     * @return array $row
     */
    public static function getUser(){
        $db = new db();
        $row = $db->selectOne('account', 'id', self::$id);
        return $row;
    }
    
    /**
    * method for displaying a logout link
    */
    public static function logout($logoutUrl = null){      
        $row = accountLogin::getUser();
        $str = '';
        $str .= lang::translate('account_logged_in_with_facebook') . ": <br />" . $row['url'];
        $str .= "<hr />" . html::createLink($logoutUrl, lang::translate('account_logout'));
        view_confirm($str);
    }
}


function account_facebook_login () {

    // check to see if user is allowed to use faccebook login
    $account_logins = config::getModuleIni('account_logins');
    if (!in_array('facebook', $account_logins)){
        moduleLoader::setStatus(403);
        return;
    }

    //$account = new accountOpenid();
    accountFacebook::init();
    if (accountFacebook::$loggedIn){

        if ($_SESSION['account_type'] != 'facebook'){
            accountLoginView::logout();
            return;
        }

    } 

    $facebook = facebook_get_object();

    // We may or may not have this data based on a $_GET or $_COOKIE based session.
    //
    // If we get a session here, it means we found a correctly signed session using
    // the Application Secret only Facebook and the Application know. We dont know
    // if it is still valid until we make an API call using the session. A session
    // can become invalid if it has already expired (should not be getting the
    // session back in this case) or if the user logged out of Facebook.
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
            print_r($e);
        }
    } else {
        $user_profile = null;
    }

    // login or logout url will be needed depending on current user state.
    if ($user_profile) {
        $account = new accountFacebook();
        $row = $account->auth($user_profile['link']);

        if (empty($row)){       
            $account->createUser();
        } else {
            $account->loginUser($row);
        }
        
        account::redirectOnLogin ();

    } else {
        session::killSession();
        $scope = facebook_get_scope();
        $loginUrl = $facebook->getLoginUrl(
            array(
                'scope' => $scope,
            )
        );
    }

    // display login or logout
    if ($user) { 
        echo html::createLink($logoutUrl, 'Log Out');
    } else {
        echo html::createLink($loginUrl, 'Log in');
    }
    
}

function facebook_get_object () {
    static $facebook = null;
    if (!$facebook)
        $facebook = new Facebook(array(
            'appId'  => config::getModuleIni('account_facebook_api_appid'),
            'secret' => config::getModuleIni('account_facebook_api_secret'),
            /*'cookie' => true, */ 
        ));
    return $facebook;
}

function facebook_get_user_profile () {
    
    $facebook = facebook_get_object();
    $user = $facebook->getUser();
    if ($user) {
        try {
            // Proceed knowing you have a logged in user who's authenticated.
            $user_profile = $facebook->api('/me');
        } catch (FacebookApiException $e) {
            print_r($e);
        }
    } else {
        $user_profile = null;
    }
    return $user_profile;
}

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
