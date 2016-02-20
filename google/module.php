<?php

namespace modules\account\google;

use diversen\conf;
use diversen\db;
use diversen\db\q;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\moduleloader;
use diversen\random;
use diversen\session;
use diversen\strings\mb;
use diversen\template;
use diversen\user;

use Google_Client;
use Google_Service_Oauth2;

moduleloader::includeModule('account');

use modules\account\module as account;
use modules\account\views as viewsAccount;
use modules\account\config;

/**
 * contains class for logging in with google api api
 * @package account
 */
class module extends account {

    
    /**
     * constructs accountLogin object.
     * set options 
     * @param array $options. Options can be:
     *              redirect on login: 'redirect' => '/path/to/redirect'
     *              accept accounts not verified: 'verified' => false  
     */
    public function __construct($options = null) {
        $this->options = array (
            'keep_session' => 1,
            'unique_email' => 1
            
        );
    } 
    
    public function getGoogleClient () {
        
        $client = new Google_Client();
        
        $client->setClientId(conf::getModuleIni('account_google_id'));
        $client->setClientSecret(conf::getModuleIni('account_google_secret'));
        $client->setRedirectUri(conf::getModuleIni('account_google_redirect'));
        //https://www.googleapis.com/auth/plus.login
        //$scope = "https://www.googleapis.com/auth/plus.me";
        $scope = 'https://www.googleapis.com/auth/userinfo.email';
        //$scope = 'email';
        $client->setScopes($scope);
        //$client->setScopes("https://www.googleapis.com/auth/plus.login");
        return $client;
    }
    
    
    /**
     * set access token
     */
    public function redirectAction () {
       
        $client = $this->getGoogleClient();
        if (isset($_GET['code'])) {
            $client->authenticate($_GET['code']);
            $_SESSION['access_token'] = $client->getAccessToken();
            http::locationHeader('/account/google/redirect');
        } 

        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $client->setAccessToken($_SESSION['access_token']);
        } else {
            session::setActionMessage(lang::translate('No google access token'));
            http::locationHeader('/account/google/index');
        }

        // get info
        if ($client->getAccessToken()) {
            $plus = new Google_Service_Oauth2($client);
            $info = $plus->userinfo->get();
            $_SESSION['access_token'] = $client->getAccessToken();
            
            if (!isset($info->verifiedEmail) || $info->verifiedEmail != 1) {
                $this->errors[]= lang::translate('Your google email needs to be verified');
                echo html::getErrors($this->errors);
                return;
            }
            
            // auth
            $values = array (
                'email' => mb::tolower($info->email),
                'url' => $info->link,  
                'type' => 'google'
            );
            
            $this->auth($values);
        } else {
            echo lang::translate('Could not get google client access token. Try again later.');
            return;
        }
        return;        
    }
    
    public function indexAction() {

        template::setTitle(lang::translate('Log in or Log out'));
        usleep(100000);

        // check to see if user is allowed to use google login
        if (!in_array('google', conf::getModuleIni('account_logins'))) {
            moduleloader::setStatus(403);
            return;
        }

        $login = new self();
        $login->setAcceptUniqueOnlyEmail(true);
        
        
        if (session::isUser()){
            $this->displayLogout();    
        } else {
            
            $client = $this->getGoogleClient();            
            $authUrl = $client->createAuthUrl();

            echo html::createLink($authUrl, lang::translate('Google login'));
            echo "<br /><br />" . viewsAccount::getTermsLink();
            
        }
        return;
    }



    
    /**
     * method for authorizing a user
     *
     * @param   string  $array ('email', 'verifiedEmail', 'link') 
     *                  array ('verified' => false', 'md5_password' => true) // if you don't require
     *                  the login creds to be verified. 
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function auth($search) {

        $account = $this->googleAccountExist($search);        
        if (!empty($account)) {
            $this->doLogin($account);
        }

        // does any account wth this email exist - check main accounts
        $account = $this->getUserFromEmail($search['email']);
        $this->checkAccountFlags($account);
        if (!empty($this->errors)) {
            echo html::getErrors($this->errors);
            return;
        }
        
        // If account exists we auto merge because we trust
        // a verified google email
        // create a sub account
        if (!empty($account)) {
            $res = $this->autoMergeAccounts($search, $account['id']);
            if ($res) {
                $this->doLogin($account);
                return;
            } else {
                echo html::getError(lang::translate('We could not merge accounts. Try again later.'));
                return;
            } 
        }

        $search['md5_key'] =random::md5();
        $search['verified'] = 1;
        $search['type'] = 'google';

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
        $this->setSessionAndCookie($account, 'google');               
        $this->redirectOnLogin();
    }
    
        /**
     * method for authorizing a user
     *
     * @param   string  username
     * @param   string  password
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function googleAccountExist ($params){
        
        // first check for a sub account and return parent account
        $db = new db();
        $search = array ('url' => $params['url'], 'type' => 'google');
        $row = $db->selectOne('account_sub', null, $search);
        if (!empty($row)) {
            $row = $db->selectOne('account', null, array ('id' => $row['parent']));
            $row = $this->checkAccountFlags($row);
            return $row;
        } 
        
        // check main account
        $search = array ('url' => $params['url'], 'type' => 'google');
        $row = $db->selectOne('account', null, $search);
        $row = $this->checkAccountFlags($row);
        return $row;
    }
    
    /**
     * auto merge two accounts
     * @param objct $ary array with google email and profile link 
     * @param int $user_id
     * @return int|false $parent_id main account id
     */
    public function autoMergeAccounts($search, $user_id) {

        $res_create = $this->createUserSub($search, $user_id);
        if ($res_create) {
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
            'email' => $search['email'],
            'type' => 'google',
            'verified' => 1,
            'parent' => $user_id);
        
        
        $res = $db->insert('account_sub', $values);
        if ($res) {
            return $db->lastInsertId();
        }
        return $res;
    }
}
