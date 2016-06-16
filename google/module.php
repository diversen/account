<?php

namespace modules\account\google;

use diversen\conf;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\moduleloader;
use diversen\session;
use diversen\strings\mb;
use diversen\template;

use Google_Client;
use Google_Service_Oauth2;

moduleloader::includeModule('account');

use modules\account\module as account;
use modules\account\views as viewsAccount;

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
            'keep_session' => 1
        );
    } 
    
    /**
     * Creates a new google client for auth
     * @return Google_Client
     */
    public function getGoogleClient () {
        
        $client = new Google_Client();
        
        $client->setClientId(conf::getModuleIni('account_google_id'));
        $client->setClientSecret(conf::getModuleIni('account_google_secret'));
        $client->setRedirectUri(conf::getModuleIni('account_google_redirect'));

        // Check for a scope in ini settings
        $scope = conf::getModuleIni('account_google_scope');
        if (!$scope) {
            $scope = 'userinfo.email';
        }
        
        $scope = "https://www.googleapis.com/auth/$scope";
        $client->setScopes($scope);
        return $client;
    }
    
    
    /**
     * /account/google/redirect redirect action after return from google
     */
    public function redirectAction () {
       
        $client = $this->getGoogleClient();
        
        // If isset code - authenticate and set access token
        // Redirect back
        if (isset($_GET['code'])) {
            $client->authenticate($_GET['code']);
            $_SESSION['access_token'] = $client->getAccessToken();
            http::locationHeader('/account/google/redirect');
        } 

        // Set access token
        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $client->setAccessToken($_SESSION['access_token']);
        } else {
            session::setActionMessage(lang::translate('No google access token'));
            http::locationHeader('/account/google/index');
        }

        // Get info
        if ($client->getAccessToken()) {
            $plus = new Google_Service_Oauth2($client);
            $info = $plus->userinfo->get();
            $_SESSION['access_token'] = $client->getAccessToken();
            
            // Check for email and verifiedEmail
            if (!isset($info->verifiedEmail) || $info->verifiedEmail != 1) {
                $this->errors[]= lang::translate('Your google email needs to be verified');
                echo html::getErrors($this->errors);
                return;
            }
            
            // Check if email is valid
            
            $c = new \modules\account\create\module();
            $c->validateEmailDomains($info->email);
        
            if (!empty($c->errors)) {
                echo html::getErrors($c->errors);
                return;
            }

            
            return $this->auth(mb::tolower($info->email), 'google');
        } else {
            echo lang::translate('Could not get google client access token. Try again later.');
            return;
        }
        return;        
    }
    
    /**
     * /account/google/index action
     * @return void
     */
    public function indexAction() {

        template::setTitle(lang::translate('Log in or Log out'));
        usleep(100000);

        // check to see if user is allowed to use google login
        if (!in_array('google', conf::getModuleIni('account_logins'))) {
            moduleloader::setStatus(403);
            return;
        }
        
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
}
