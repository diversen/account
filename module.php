<?php

namespace modules\account;

use diversen\conf;
use diversen\db;
use diversen\db\q;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\random;
use diversen\session;
use diversen\strings\mb;
use diversen\user;
use modules\account\config;

/**
 * Account class
 */
class module {

    /**
     * var holding errors
     * @var array $errors
     */
    public $errors = array();

    /**
     * var holding options
     * @var type
     */
    public $options = array();

    /**
     * status to give on login
     * @var string $str
     */
    public $status = '';

    /**
     *
     * @param array $options
     */
    public function __construct($options = array()) {
        $this->options = $options;
    }

    /**
     * Logout action
     * Logout when going to URL  /account/logout
     *
     * this calls $this->doLogout();
     */
    public function logoutAction() {

        $this->killSession();
        $redirect = $this->getLogoutRedirect();
        http::locationHeader($redirect);
    }
    
    /**
     * Logoutall action. Logs users out of all devices
     * Logout when going to URL  /account/logoutall
     *
     * this calls $this->doLogout();
     */
    public function logoutallAction() {

        $this->killSession($all = true);
        $redirect = $this->getLogoutRedirect();
        http::locationHeader($redirect);
    }
    
    /**
     * Kills session
     * @param boolean $all kill all sessions on all devices
     */
    public function killSession ($all = false) {
        
        if ($all) {
            session::killAllSessions(session::getUserId());
        } else {
            session::killSession();
        }
        session_regenerate_id(true);
        $_SESSION = array();

    } 
    
    /**
     * Get logout redirect
     * @return string $redirect
     */
    public function getLogoutRedirect () {
        // If is GET redirect.
        if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
            $redirect = $_GET['redirect'];
        } elseif (conf::getModuleIni('account_redirect_logout')) {
            $redirect = conf::getModuleIni('account_redirect_logout');
        } else {
            $redirect = $this->getDefaultLoginRedirect();
        }
        return $redirect;
    }

    /**
     * set value of $this->options['keep_session']
     * needs to be set before login.
     * @param boolean $bool true if we want to set persistent cookie.
     */
    public function setPersistentCookie($bool = true) {
        if ($bool) {
            $this->options['keep_session'] = true;
        }
    }

    /**
     * set a redirect on login
     * @param string $redirect e.g. '/mypage/test'
     */
    public function setRedirect($redirect = null) {
        if (!$redirect) {
            return;
        }
        $this->options['redirect'] = $redirect;
    }

    /**
     * sets session and system cookie on login
     * we know user is authenticated and all we need is to set
     * the SESSION and system cookies.
     *
     * @param array $account
     */
    public function setSessionAndCookie($account, $type = 'email') {
        $_SESSION['id'] = $account['id'];
        $_SESSION['admin'] = $account['admin'];
        $_SESSION['super'] = $account['super'];
        $_SESSION['account_type'] = $type;

        session::setSystemCookie($account['id']);
        config::onLogin($account['id']);
    }

    /**
     * method for creating a logout link. Fetch info from
     * a profile to show how to display logut
     */
    public static function displayLogout() {
        $row = user::getAccount(session::getUserId());
        echo user::getLogoutHTML($row);
    }
    
    /**
     * Authenticate an user based on email and type e.g. 'google' or 'facebook'
     * @param string $email
     * @param string $type
     * @return 
     */
    public function auth ($email, $type) {
        
        $email = mb::tolower($email);
        // Check if 'type' and 'email' - in both account and account_sub
        // Sub modules like e.g. github, gmail, facebook needs to decide
        // if the email is authorized
        $account = $this->getAccountFromEmailAndType($email, $type);
        if (!empty($account)) {
            $this->doLogin($account, $type);
            return;
        }
        
        // Does any account with this email exist - check main accounts
        $account = $this->getUserFromEmail($email, null);
        $account = $this->checkAccountFlags($account);
        if (!empty($this->errors)) {
            echo html::getErrors($this->errors);
            return;
        }
        
        // If an account exists now then we auto merge because we trust a verified email
        // Create a sub account
        if (!empty($account)) {
            $res = $this->autoMergeAccounts($email, $account['id'], $type);
            if ($res) {
                $this->doLogin($account, $type);
                return;
            } else {
                echo html::getError(lang::translate('We could not merge accounts. Try again later.'));
                return;
            } 
        }

        // Account does not exists - but is authorized.
        // Create account
        $search['email'] = $email;
        $search['md5_key'] =random::md5();
        $search['verified'] = 1;
        $search['type'] = $type;

        q::begin();
        q::insert('account')->values($search)->exec();
        $last_id = q::lastInsertId();
        q::commit();
        
        // events
        config::onCreateUser($last_id);
       
        $this->doLogin(user::getAccount($last_id));
    }

    /**
     *
     * /account/index action
     */
    public function indexAction() {

        // set a session var
        if (isset($_GET['return_to'])) {
            $_SESSION['return_to'] = rawurldecode($_GET['return_to']);
        }

        if (isset($_GET['message'])) {
            session::setActionMessage(rawurldecode(html::specialEncode($_GET['message'])));
        }

        $account_default = conf::getModuleIni('account_default_url');
        if (!isset($account_default)) {
            $account_default = '/account/login/index';
        }
        http::locationHeader($account_default);

    }

    /**
     * Method for redireting to default login url. The default URL is set
     * in the ini setting 'account_default_url'
     */
    public function redirectDefault() {
        $default = $this->getDefaultLoginRedirect();
        http::locationHeader($default);
    }

    /**
     * Checks if we need to redirect to aspecified URL on login
     * examines latest $_SESSION['redirect_on_login']
     * @param string $url
     */
    public function redirectOnLogin($url = null) {

        // if session return_to has been set we will use this as redirect
        // else redirect to URL given or if null redirect to default account url
        if (isset($_SESSION['return_to'])) {
            $redirect = $_SESSION['return_to'];
            unset($_SESSION['return_to']);
            http::locationHeader($redirect);
        } else {
            if ($url) {
                $location = $url;
            } else {
                $location = $this->getDefaultLoginRedirect();
            }
            $message = lang::translate('You are logged in');
            http::locationHeader($location, $message);
        }
    }

    /**
     * If redirect_login is set we use this as default redirect.
     * if not we redirect to the default account url
     * @return string $location
     */
    public function getDefaultLoginRedirect() {

        if (conf::getModuleIni('account_redirect_login')) {
            $location = conf::getModuleIni('account_redirect_login');
        } else {
            $location = conf::getModuleIni('account_default_url');
        }
        if (!$location) {
            $location = '/';
        }
        return $location;
    }

    /**
     * Get account from id
     * @param int $id
     * @return array $row
     */
    public function searchIdOrEmail($id) {
        return q::setSelect('account')->
                        filter('id = ', $id)->
                        condition('OR')->
                        filter('email =', $id)->
                        fetch();
    }

    /**
     * auth from md5 key
     * @param string $md5
     * @return boolean true on success and false on failure
     */
    public function authFromMd5($md5) {
        $db = new db();
        $search = array('md5_key' => $md5);

        $row = $db->selectOne('account', null, $search);
        $row = $this->checkAccountFlags($row);
        if (!empty($row)) {
            $this->setSessionAndCookie($row);
            return true;
        }
        return false;
    }

    /**
     * returns parent account
     * used when auto merging sub accounts
     * @param int $id usub accounts user_id
     * @return array $row parent account row
     */
    public function getParentAccount($id) {
        $db = new db();
        return $db->selectOne('account_sub', 'id', $id);
    }

    /**
     * Select an account row from. Don't need to be verified 
     * @param string $email
     * @param null|string $type defaults to email. Use 'null' if you want to
     *                    fetch from any type of account. Or set type to e.g.
     *                    google or facebook
     * @return array $row the user's account row. Empty row if there is no user
     *                    with requested email
     */
    public function getUserFromEmail($email, $type = 'email', $check_sub = null) {
        $db = new db();

        $search = array();
        $search['email'] = mb::tolower($email);
        if ($type !== null) {
            $search['type'] = $type;
        }

        if ($check_sub) {
            $row = $db->selectOne('account_sub', null, $search);
            if (!empty($row)) {
                $parent = $db->selectOne('account', 'id', $row['parent']);
                return $parent;
            }
        }

        $row = $db->selectOne('account', null, $search);
        return $row;
    }
    
    /**
     * Get a main account from email and type (e.g. 'google' or 'facebook')
     * @param string $email
     * @param string $type
     * @return array $account
     */
    public function getAccountFromEmailAndType ($email, $type = 'email') {
        
        // first check for a sub account and return parent account
        $db = new db();
        $search = array ('email' => $email, 'type' => $type);
        $row = $db->selectOne('account_sub', null, $search);
        if (!empty($row)) {
            $row = $db->selectOne('account', null, array ('id' => $row['parent']));
            $row = $this->checkAccountFlags($row);
            return $row;
        } 
        
        // check main account
        $search = array ('email' => $email, 'type' => $type);
        $row = $db->selectOne('account', null, $search);
        $row = $this->checkAccountFlags($row);
        return $row;
        
    }

    /**
     * Method for checking if a email exists in `account` table
     * @param   string  $email the email to be checked for existens
     * @return  array|false  $row user row or false
     */
    public function emailExistInAccount($email) {
        $row = $this->getUserFromEmail($email, null);
        if (empty($row)) {
            return false;
        }
        return $row;
    }

    /**
     * method for checking if auth row is verified.
     * @param array $row
     * @return array $row original row or empty array if we don't allow non
     *                    verified accounts
     */
    public function checkVerified($row) {
        if ($row['verified'] == 1) {
            return $row;
        } else {
            $this->errors['not_verified'] = lang::translate('Account needs to be verified before you may log in');
            $this->errors['type'] = lang::translate('Main account is of this type: ') . $row['type'];
            return [];
        }
    }

    /**
     * Checks if account is logged
     * @param row $row
     * @return row $row
     */
    public function checkLocked($row) {
        if ($row['locked'] == 1) {
            $this->errors['locked'] = lang::translate('This account has been locked');
            return array();
        }
        return $row;
    }

    /**
     * Combined check of locked account and check of verified account
     * @param array $row
     * @return array $row account row or empty row if user is not valid.
     */
    public function checkAccountFlags($row) {
        if (empty($row)) {
            return $row;
        }
        $row = $this->checkLocked($row);
        return $row;
    }
    
    /**
     * Merge accounts. Based on email
     * @param objct $ary array with google email and profile link 
     * @param int $user_id
     * @return int|false $parent_id main account id
     */
    public function autoMergeAccounts($email, $user_id, $type) {

        $res_create = $this->createUserSub($email, $user_id, $type);
        if ($res_create) {
            return $user_id;
        }

        return false;
    }

    /**
     * Method for creating a sub user
     * @return int|false $res last_isnert_id on success or false on failure
     */
    public function createUserSub ($email, $user_id, $type){
        
        $db = new db();
        $values = array(
            'email' => mb::tolower($email),
            'type' => $type,
            'verified' => 1,
            'parent' => $user_id);    
        
        $res = $db->insert('account_sub', $values);
        if ($res) {
            return $db->lastInsertId();
        }
        return $res;
    }
    
    /**
     * sets session and cookie
     * @param array $account
     * @return boolean $res
     */
    public function doLogin ($account, $type) {
        $this->setSessionAndCookie($account, $type);               
        $this->redirectOnLogin();
    }
}
