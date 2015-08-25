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
use modules\account\views as accountViews;

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

        $this->setAcceptUniqueOnlyEmail(true);
        $this->auth();

        if (!empty($this->errors)) {
            echo html::getErrors($this->errors);
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

        $this->setAcceptUniqueOnlyEmail(true);
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
            echo "<br /><br />" . accountViews::getTermsLink();
        }
    }

    /**
     * method for authorizing a user
     *
     * @param   string  $email
     * @param   string  $password
     * @param   array   $params 
     *                  array ('verified' => false', 'md5_password' => true) // if you don't require
     *                  the login creds to be verified. 
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function auth() {
        $api = new githubapi();
        $res = $api->apiCall('/user');
        
        // user id is unique - we use this as 'url' which is unique
        $res['id'] = (int) $res['id'];

        // check if user exists in db
        if (isset($res['id']) && !empty($res['id'])) {

            // generate user we search for
            $db = new db();
            $search = array(
                'type' => 'github',
                'url' => $res['id'],
                'email' => mb::tolower($res['email']),
            );

            $account = $this->githubAccountExist($search);

            // account exists - login and redirect
            if (!empty($account)) {
                $this->doLogin($account);
            }

            // New account
            // Check if we use unique email only - one account per user
            $account = $this->getUserFromEmail($search['email'], null);
            if (!empty($account)) {
                $res = $this->autoMergeAccounts($search, $account['id']);
                $this->doLogin($account);
            } else {
                return $this->createAccount($search);
            }
        }
    }
    
    /**
     * create account and redirect
     * @param array $search basic 
     * @return type
     */
    public function createAccount($search) {
        
        $db = new db();
        $search['verified'] = 1;
        $db->begin();
        $db->insert('account', $search);
        $db->commit();
        $last_insert_id = $db->lastInsertId();
        
        config::onCreateUser($last_insert_id);
        
        $account = user::getAccount($last_insert_id);
        return $this->doLogin($account);
    }

    /**
     * sets session and cookie
     * @param array $account
     * @return boolean $res
     */
    public function doLogin($account) {
        $this->setPersistentCookie(true);
        $this->setSessionAndCookie($account);
        $this->redirectOnLogin();
    }

    /**
     * method for authorizing a user
     *
     * @param   string  username
     * @param   string  password
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function githubAccountExist($params) {

        // first check for a sub account and return parent account
        $db = new db();
        $search = array('url' => $params['url'], 'type' => 'github');
        $row = $db->selectOne('account_sub', null, $search);
        if (!empty($row)) {
            $row = $db->selectOne('account', null, array('id' => $row['parent']));
            $row = $this->checkLocked($row);
            return $row;
        }

        // check main account
        $search = array('url' => $params['url'], 'type' => 'github');
        $row = $db->selectOne('account', null, $search);
        $row = $this->checkLocked($row);
        return $row;
    }

    /**
     * auto merge two accounts
     * @param objct $openid lightopenid object
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
    public function createUserSub($search, $user_id) {

        $db = new db();
        $values = array(
            'url' => $search['url'],
            'email' => mb::tolower($search['email']),
            'type' => 'github',
            'verified' => 1,
            'parent' => $user_id);

        $db->begin();
        $db->insert('account_sub', $values);
        $res = $db->commit();
        if ($res) {
            return $db->lastInsertId();
        }
        return $res;
    }
}
