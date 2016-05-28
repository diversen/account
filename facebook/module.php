<?php

namespace modules\account\facebook;

// Facebook


use diversen\conf;
use diversen\db;
use diversen\db\q;
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
use modules\account\config;
use modules\account\facebook\views as account_facebook_views;
use modules\account\module as account;
use modules\account\views as viewsAccount;

moduleloader::includeModule('account/login');
view::includeOverrideFunctions('account', 'facebook/views.php');

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

    public function auth ($email){
     
        // first check for a sub account and return parent account
        //$db = new db();
        $search = array ('email' => $email, 'type' => 'facebook');
        
        $row = $db->selectOne('account_sub', null, $search);

        if (!empty($row)) { 
            $row = $db->selectOne('account', null, array ('id' => $row['parent']));
            $row = $this->checkLocked($row);
            return $row;
        } 
        
        print_r(db::getDebug());
        die;
        // check main account
       
        $row = $db->selectOne('account', null, $search);
        if (!empty($row)) {
            return $this->checkLocked($row);        
        }
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
            
            // run events
            config::onCreateUser($id);
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
        return $id;
    }
    
    /**
     * method for creating a sub user
     *
     * @return int|false $res last_isnert_id on success or false on failure
     */
    public function createUserSub ($user, $user_id){
        
        $db = new db();
        $row = $db->selectOne('account_sub', 'email', $user->getEmail());
        if (!empty($row)) {
            return array ();
        }
        
        $values = array(
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
    

    public function getFbObject ($token = null) {
        
        $app_id = conf::getModuleIni('account_facebook_api_appid');
        $app_secret = conf::getModuleIni('account_facebook_api_secret');

        // FacebookSession::setDefaultApplication($app_id , $app_secret);
        // $helper = new FacebookRedirectLoginHelper($redirect_url);
             
        $ary = array (
            'app_id' => $app_id,
            'app_secret' => $app_secret,
            'default_graph_version' => 'v2.6'
        );

        $fb = new \Facebook\Facebook($ary);        
        return $fb;
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

        
        $fb = $this->getFbObject();
        $helper = $fb->getRedirectLoginHelper();
        
        try {
            // Get the Facebook\GraphNodes\GraphUser object for the current user.
            // If you provided a 'default_access_token', the '{access-token}' is optional.
            // $response = $fb->get('/me', '{access-token}');
            $accessToken = $helper->getAccessToken();
            $_SESSION['facebook_access_token'] = (string) $accessToken;
            // $response = $fb->get('/me');
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            $message = 'Graph returned an error: ' . $e->getMessage();
            log::error($message);
            return false;
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            $message = 'Facebook SDK returned an error: ' . $e->getMessage();
            log::error($message);
            return false;
        }
        //display login url
        // $scope = ;
        $redirect_url = conf::getSchemeWithServerName() . "/account/facebook/callback";
        $login_url = $helper->getLoginUrl($redirect_url, $this->getScope() );
        account_facebook_views::loginLink ($login_url);
        echo "<br /><br />" . viewsAccount::getTermsLink();
        
    }
    
    public function callbackAction() {

        $fb = $this->getFbObject();
        $helper = $fb->getRedirectLoginHelper();

        // If no email has been supplied
        if (isset($_GET['revoke'])) {
            $this->errors[] = lang::translate('We will need your email. No login without email. Please try again!');
            
            $fb = $this->getFbObject();
            $helper = $fb->getRedirectLoginHelper();
            
            $accessToken = $helper->getAccessToken();
            $response = $fb->delete('/me/permissions', array('email'), $_SESSION['facebook_access_token'] );
            echo html::getErrors($this->errors);
            return false;
        }

        try {
            // Get the Facebook\GraphNodes\GraphUser object for the current user.
            // If you provided a 'default_access_token', the '{access-token}' is optional.
            $accessToken = $helper->getAccessToken();
            $_SESSION['facebook_access_token'] = (string) $accessToken;
            $response = $fb->get('/me?fields=name,email', $accessToken);
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            $error = 'Graph returned an error: ' . $e->getMessage();
            log::error($error);
            return;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            $error = 'Facebook SDK returned an error: ' . $e->getMessage();
            log::error($error);
            return;
        }

        
        $me = $response->getGraphUser();
        if (!$me->getEmail()) {
            // print_r($me); die;
            
            \diversen\http::locationHeader('/account/facebook/callback?revoke=1');
            return;
        }

        print_r($me);
        // does user exists and is he already registered
        $row = $this->auth($me->getEmail());
        print_r($row);
        // no errors. 
        if (empty($row)) {
            $id = $this->createUser($me);
            $row = user::getAccount($id);
        }

        print_r($row); die;
        // set session and cookie
        $this->setSessionAndCookie($row);
        if (empty($this->errors)) {
            $this->redirectOnLogin();
        } else {
            echo html::getErrors($this->errors);
            return false;
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
        
        $scope_ary = explode(",", $scope);
        return $scope_ary;
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
