<?php

namespace modules\account\github;
/**
 * contains class for logging in with github api
 * @package account
 */

use diversen\conf;
use diversen\db;
use diversen\githubapi;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\moduleloader;
use diversen\random;
use diversen\session;
use diversen\strings\mb;
use diversen\template;
use diversen\user;

use modules\account\config;
use modules\account\module as account;
use modules\account\views as viewsAccount;

/**
 * contains class for logging in with github api
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
        $this->options = $options;
    }

    /**
     * acccount/github/callback ation
     */
    public function callbackAction() {
        $redirect_uri = conf::getSchemeWithServerName() . "/account/github/callback";
        $post = array(
            'redirect_uri' => $redirect_uri,
            'client_id' => conf::getModuleIni('account_github_id'),
            'client_secret' => conf::getModuleIni('account_github_secret'),
        );


        $api = new githubapi();
        $res = $api->setAccessToken($post);

        if ($res) {
            http::locationHeader('/account/github/api');
        } else {
            echo "Could not get access token. Errors: <br />";
            echo html::getErrors($api->errors);
        }
    }
    
    /**
     * account/github/api action
     */
    public function apiAction() {
        
        $api = new githubapi();
        $res = $api->apiCall('/user');
        
        // user id is unique - we use this as 'url' which is unique
        $res['id'] = (int) $res['id'];
        
        // check if user exists in db
        if (isset($res['id']) && !empty($res['id'])) {
            $this->auth($res['email'], 'github');
        }
    }

    /**
     * /account/github/index action
     * @return void
     */
    public function indexAction() {
        template::setTitle(lang::translate('Log in or Log out'));

        usleep(100000);

        // check to see if user is allowed to use github login
        if (!in_array('github', conf::getModuleIni('account_logins'))) {
            moduleloader::setStatus(403);
            return;
        }

        $this->setPersistentCookie(true);
        $this->controlLogin();
    }

    /**
     * display github access (login link with required scope
     */
    public function login() {

        $callback = conf::getSchemeWithServerName() . "/account/github/callback";
        $access_config = array(
            'redirect_uri' => $callback,
            'client_id' => conf::getModuleIni('account_github_id'),
            'state' => random::md5(),
        );

        $scope = conf::getModuleIni('account_github_scope');
        if ($scope) {
            $access_config['scope'] = $scope;
        }

        // login
        $api = new githubapi();
        $url = $api->getAccessUrl($access_config);
        echo html::createLink($url, lang::translate('Github login'));
    }

    /**
     * display login og logout 
     */
    public function controlLogin() {
        if (session::isUser()) {
            $this->displayLogout();   
        } else {
            $this->login();
            echo "<br /><br />" . viewsAccount::getTermsLink();
        }
    }
}
