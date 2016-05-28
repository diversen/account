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
            \diversen\http::locationHeader('/account/facebook/callback?revoke=1');
            return;
        }

        // Check if sub account. Also check if locked
        $account = $this->getAccountFromEmailAndType($me->getEmail(), 'facebook');
        if (!empty($account)) {
            $this->doLogin($account);
            return;
        }
        
        // Does any account with this email exist - check main accounts
        $account = $this->getUserFromEmail($me->getEmail());
        $account = $this->checkAccountFlags($account);
        if (!empty($this->errors)) {
            echo html::getErrors($this->errors);
            return;
        }
        
        // If account exists we auto merge because we trust a verified google email
        // Create a sub account
        if (!empty($account)) {
            $res = $this->autoMergeAccounts($me->getEmail(), $account['id'], 'facebook');
            if ($res) {
                $this->doLogin($account);
                return;
            } else {
                echo html::getError(lang::translate('We could not merge accounts. Try again later.'));
                return;
            } 
        }

        // Account does not exists - but is authorized.
        // Create account
        $search['email'] = $me->getEmail();
        $search['md5_key'] =random::md5();
        $search['verified'] = 1;
        $search['type'] = 'facebook';

        q::begin();
        q::insert('account')->values($search)->exec();
        $last_id = q::lastInsertId();
        q::commit();
        
        // events
        config::onCreateUser($last_id);
       
        return $this->doLogin(user::getAccount($last_id));
    }
    
    /**
     * sets session and cookie
     * @param array $account
     * @return boolean $res
     */
    public function doLogin ($account) {
        $this->setSessionAndCookie($account, 'facebook');               
        $this->redirectOnLogin();
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
