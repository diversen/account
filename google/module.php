<?php

moduleloader::includeModule('account');

/**
 * contains class for logging in with google api api
 * @package account
 */
class account_google extends account {

    
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
        
        
        
        $client->setClientId(config::getModuleIni('account_google_id'));
        $client->setClientSecret(config::getModuleIni('account_google_secret'));
        $client->setRedirectUri(config::getModuleIni('account_google_redirect'));
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
            header('Location: /account/google/redirect');
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
                echo html::getError(lang::translate('Your google email needs to be verified'));
                return;
            }
            
            // auth
            $res = array (
                'email' => strings_mb::tolower($info->email),
                'url' => $info->link,  
                'type' => 'google'
            );
            
            $this->auth($res);
        }
        return;        
    }
    
    public function indexAction() {
        
        
        template::setTitle(lang::translate('Log in or Log out'));
        usleep(100000);

        // check to see if user is allowed to use google login
        if (!in_array('google', config::getModuleIni('account_logins'))) {
            moduleloader::setStatus(403);
            return;
        }

        $login = new account_google();
        $login->setAcceptUniqueOnlyEmail(true);
        
        
        if (session::isUser()){
            $this->displayLogout();  
        // submission has taking place but no redirect.     
        } else {
            
            $client = $this->getGoogleClient();
           // $client = $this->getGoogleClient();
            
            
            $authUrl = $client->createAuthUrl();

            
            //$this->login();
            echo html::createLink($authUrl, lang::translate('Google login'));
            echo "<br /><br />" . account_views::getTermsLink();
            
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
        $account = $this->getUserFromEmail($search['email'], null);
        
        // if account exists we auto merge because we trust
        // a verified google email
        // create a sub account
        if (!empty($account)) {
            $res = $this->autoMergeAccounts($search, $account['id']);
            if ($res) {
                $this->doLogin($account);
            } else {
                html::getError(lang::translate('Something really weird happened. TRy again!'));
            }
            return;
        }

        $search['md5_key'] =random::md5();
        $search['verified'] = 1;
        $search['type'] = 'google';

        db_q::begin();
        db_q::insert('account')->values($search)->exec();
        $last_id = db_q::lastInsertId();
        db_q::commit();
        return $this->doLogin(user::getAccount($last_id));

    }

    /**
     * sets session and cookie
     * @param array $account
     * @return boolean $res
     */
    public function doLogin ($account) {
        $this->setSessionAndCookie($account, 'google');               
        if ($this->options['redirect'] === false) {
            return true;
        }

        if (isset($this->options['redirect'])) {
            $this->redirectOnLogin($this->options['redirect']);
        } else {
            $this->redirectOnLogin();
        } 
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
    public function autoMergeAccounts ($search, $user_id) {
        
        // examine if we are allowed to merge this URL
        $allow_merge = config::getModuleIni('account_auto_merge');
        $res = false;
        foreach($allow_merge as $host) {
            if ($host == 'google') {
                $res = true;                
                break;
            }
        }
        
        if ($res) {
            $res_create = $this->createUserSub($search, $user_id);
            if ($res_create) {
                
                // run account_connect events
                $args = array (
                    'action' => 'account_connect',
                    'user_id' => $user_id,
                );

                event::getTriggerEvent(
                    config::getModuleIni('account_events'), 
                    $args
                );
                
                return $user_id;   
            }
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
        
        // If not isset options verified - we allow non verified account to log in
        if (isset($this->options['verified']) && !$this->options['verified']) {
            unset($values['verified']);
        }
        
        $res = $db->insert('account_sub', $values);
        if ($res) {
            return $db->lastInsertId();
        }
        return $res;
    }
}