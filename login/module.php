<?php

namespace modules\account\login;

use diversen\db;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\session;
use diversen\strings\mb;
use diversen\template;
use diversen\conf;

use modules\account\module as account;

/**
 * Class for logging in users with email and password, and letting the request
 * new password
 */
class module extends account {

    /**
     * Constructor. Sets options
     * @param array $options  array ('redirect' => '/path/to/redirect', 'verified' => false, 'keep_session' => true)
     *              Set redirect after login
     *              Accept accounts that are not verified: 
     *              Keep session in system cookie, so that user statys logged in when browser is closed 
     *                          
     */
    public function __construct($options = null) {
        $this->options = $options;
    }

    /**
     * Default page action to be performed on /account/login/index page
     * only verified users, check for keep session
     */
    public function indexAction() {
        usleep(100000);

        http::prg();
        template::setTitle(lang::translate('Log in or Log out'));
        
        // Check if we want to keep session
        if (isset($_POST['keep_session']) && $_POST['keep_session'] == 1) {
            $this->options['keep_session'] = 1;
        }

        // Is is logged in
        if (session::isUser()) {         
            $this->displayLogout();
            return;
        }
        
        // POST request sent
        if (isset($_POST['email']) && isset($_POST['password'])) {     
            $account = $this->auth($_POST['email'], $_POST['password']);
            if (!empty($account)) {
                $this->setSessionAndCookie($account);
                $this->redirectOnLogin();
                return;
            }
        }
        
        \modules\account\login\views::formLogin($this->errors); 
    }
    
    /**
     * Request password option
     */
    public function requestpwAction () {
        $rp = new \modules\account\requestpw\module();
        $rp->indexAction();
    }

    /**
     * Action where anon user creates account
     * /account/login/create action
     * @return void
     */
    public function createAction() {

        http::prg();

        if (!conf::getModuleIni('account_anon_create')) {
            \diversen\moduleloader::setStatus(403);
            return;
        }
        

        template::setTitle(lang::translate('Create Account'));
        $c = new \modules\account\create\module();
        if (!empty($_POST['submit'])) {
            $_POST = html::specialEncode($_POST);
            $c->validate();
            if (empty($c->errors)) {
                $res = $c->createUser();
                if ($res) {
                    http::locationHeader(
                        '/account/login/index', 
                        lang::translate('Account has been created. Visit your email box and press the verification link.'));
                } else {
                    echo html::getErrors($c->errors);
                }
            } else {
                echo html::getErrors($c->errors);
            }
        }

        echo \modules\account\login\views::formCreate();
        echo \modules\account\views::getTermsLink();
    }


    /**
     * Method for authorizing a user, check if verified, and check if locked
     * @param string $email
     * @param string $password 
     * @return array $row with user account, empty if error has been reported
     */
    public function auth($email, $password = null) {
        $row = $this->authGetAccount($email, $password);
        if (empty($row)) {
            $this->errors[] = lang::translate('Not a correct email or password');
            return [];
        }

        $row = $this->checkVerified($row);
        if (empty($row)) {
            return [];
        }

        return $this->checkLocked($row);

    }

    /**
     * Get an account from password and email
     * @param string $email
     * @param string $password
     * @return array $row
     */
    public function authGetAccount($email, $password) {
        $db = new db();

        $search = array(
            'email' => mb::tolower($email),
            'password' => md5($password),
            'type' => 'email',
        );

        $row = $db->selectOne('account', null, $search);
        return $row;
    }
}
