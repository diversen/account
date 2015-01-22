<?php

use diversen\strings\mb as strings_mb;

moduleloader::includeModule('account');
view::includeOverrideFunctions('account', 'login/views.php');

class account_login extends account {

    /**
     * constructs accountLogin object.
     * set options 
     * @param array $options. Options can be:
     *              redirect on login:            'redirect' => '/path/to/redirect'
     *              accept accounts not verified: 'verified' => false
     *              keep session in system cookie 'keep_session => true
     *                          
     */
    public function __construct($options = null) {
        $this->options = $options;
    }

    /**
     * method for doing a login from default $_POST values
     * what will be done depends on $this->options['redirect']
     * @return void|boolean $res true if no redirect is performed
     *                            void if redirect is set: will direct to set value
     *                                 or: redirect to default login url 
     */
    public function login() {
        if (isset($_POST['email']) && isset($_POST['password'])) {
            $account = $this->auth($_POST['email'], $_POST['password']);
            if (!empty($account)) {
                $this->setSessionAndCookie($account);
                return $this->redirect();
            }
        }
    }

    /**
     * redirect after valid email login
     * @return boolean|void $res true if we don't redirect
     *                            void if we redirect
     */
    public function redirect() {
        if (isset($this->options['redirect']) && ($this->options['redirect'] === false)) {
            return true;
        }

        if (isset($this->options['redirect'])) {
            $this->redirectOnLogin($this->options['redirect']);
        } else {
            $this->redirectOnLogin();
        }
    }

    /**
     * default page action to be performed on /account/login/index page
     * only verified users, check for keep session
     */
    public function indexAction() {
        usleep(100000);

        http::prg();
        template::setTitle(lang::translate('Log in or Log out'));

        $options = array();

        // check if we want to keep session
        if (isset($_POST['keep_session']) && $_POST['keep_session'] == 1) {
            $options['keep_session'] = 1;
        }

        $login = new account_login($options);
        $login->displayLogin();
    }
    
    public function requestpwAction () {
        $rp = new account_requestpw();
        $rp->requestpwAction();
    }

    public function createAction() {

        /**
         * controller file for creating a user
         */
        http::prg();

        moduleloader::includeModule('account/create');
        if (!session::checkAccessFromModuleIni('account_allow_create')) {
            return;
        }

        template::setTitle(lang::translate('Create Account'));
        $l = new account_create();
        if (!empty($_POST['submit'])) {
            $_POST = html::specialEncode($_POST);
            $l->validate();
            if (empty($l->errors)) {
                $l->createUser();
                http::locationHeader(
                        '/account/login/index', lang::translate('Account: Create notice'));
            } else {
                html::errors($l->errors);
            }
        }

        account_login_views::formCreate();
        echo account_views::getTermsLink();
    }

    /**
     * method for controlling email login 
     * this uses default controller found at 
     * 
     */
    public function displayLogin() {
        $this->login();
        if (session::isUser()) {

            $this->displayLogout();
            // submission has taking place but no redirect.     
        } elseif (isset($_POST['submit_account_login'])) {

            $this->errors[] = lang::translate('Not a correct email or password');
            $vars['errors'] = $this->errors;
            account_login_views::formLogin($vars);

            // no submission
        } else {
            account_login_views::formLogin();
        }
    }

    /**
     * method for authorizing a user
     *
     * @param   string  $email
     * @param   string  $password 
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function auth($email, $password = null) {
        $row = $this->authGetAccount($email, $password);
        return $this->checkAccountFlags($row);
    }

    /**
     * get an account from password and email
     * @param string $email
     * @param string $password
     * @return array $row
     */
    public function authGetAccount($email, $password) {
        $db = new db();

        $search = array(
            'email' => strings_mb::tolower($email),
            'password' => md5($password),
            'type' => 'email',
        );

        $row = $db->selectOne('account', null, $search);
        return $row;
    }

}
