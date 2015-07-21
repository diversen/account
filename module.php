<?php

/**
 * File containing main account class with a few shared 
 * methods between different login methods. 
 */


use diversen\strings\mb as strings_mb;


/**
 * class account 
 */
class account {
    
    /**
     * var holding errors
     * @var array $errors 
     */
    public $errors = array ();
    
    /**
     * var holding options
     * @var type 
     */
    public $options = array (
        'unique_email' => 1
    );
    
    /**
     * status to give on login
     * @var string $str
     */
    public $status = '';
    
    /**
     * 
     * @param array $options
     */
    public function __construct($options = array ()) {
        $this->options = $options;
    }
        
    
    /**
     * Logout action 
     * Logout when going to URL  /account/logout
     *
     * this calls $this->doLogout();
     */
    
    public function logoutAction () {
        
        session::killSession();
        session_regenerate_id(true);

        $_SESSION=array();
        if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
            $redirect = $_GET['redirect'];
        } else {
            $redirect = $this->getDefaultLoginRedirect();
        }
        http::locationHeader($redirect);
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
    public function setRedirect ($redirect = null) {
        if (!$redirect) { 
            return;
        }
        $this->options['redirect'] = $redirect;
        
    }
    
     
    /**
     * Allow non verified accounts. Direct login with an email without
     * a verified account
     * @param boolean $bool if false we accept non verified accounts. 
     *                      If set to true
     *                
     */
    public function setAcceptNonVerifiedAccount ($bool = true) {
        if ($bool) {
            $this->options['verified'] = false;
        } else {
            $this->options['verified'] = true;
        }
    }
    
    /**
     * flag to indicate if we only allow one account per email
     * @param boolean $bool true if we only allow one account per email else false
     */
    public function setAcceptUniqueOnlyEmail ($bool = true) {
        $this->options['unique_email'] = $bool;
    }
    
    /**
     * sets session and system cookie on login
     * we know user is authenticated and all we need is to set
     * the SESSION and system cookies.
     * 
     * @param array $account
     */
    public function setSessionAndCookie ($account, $type = 'email') {
        $_SESSION['id'] = $account['id'];
        $_SESSION['admin'] = $account['admin'];
        $_SESSION['super'] = $account['super'];
        $_SESSION['account_type'] = $type;
        
        if (isset($this->options['keep_session'])){
            session::setSystemCookie($account['id']);
        }
                
        $args = array (
            'action' => 'account_login',
            'user_id' => $account['id'],
        );
                
        event::getTriggerEvent(
            config::getModuleIni('account_events'), 
            $args
        );        
    }

   /**
    * method for creating a logout link. Fetch info from
    * a profile to show how to display logut
    */
    public static function displayLogout(){
        $row = user::getAccount(session::getUserId()); 
        echo user::getLogoutHTML($row);
    }

    /**
     * 
     * account/index action 
     */
    public function indexAction () {
        
        if (isset($_GET['return_to'])) {
            $_SESSION['return_to'] = rawurldecode($_GET['return_to']);
        }

        if (isset($_GET['message'])) {
            session::setActionMessage(rawurldecode(html::specialEncode($_GET['message'])));
        }

        $this->redirectDefault();
    }
    
    /**
     * Method for redireting to default login url. The default URL is set 
     * in the ini setting 'account_default_url'
     */
    public static function redirectDefault (){
        $default = config::getModuleIni('account_default_url');
        if (!$default) {
            $default = '/account/login/index';
        }
        http::locationHeader ($default);
    }

    /**
     * checks if we need to redirect to aspecified URL on login
     * examines latest $_SESSION['redirect_on_login']
     * @param string $url 
     */
    public static function redirectOnLogin ($url = null){
        
        // if session return_to has been set we will use this as redirect 
        // else redirect to URL given or if null redirect to default account url
        if (isset($_SESSION['return_to'])){
            $redirect = $_SESSION['return_to'];
            unset($_SESSION['return_to']);
            http::locationHeader ($redirect);
        } else {
            if ($url){
                $location = $url;
            } else {
                $location = self::getDefaultLoginRedirect();
                
            }
            http::locationHeader($location);
         }
    }
    
    /**
     * if redirect_login is set we use this as default redirect. 
     * if not we redirect to the default account url
     * @return string $location
     */
    public static function getDefaultLoginRedirect() {
        
        if (config::getModuleIni('account_redirect_login')) {
            $location = config::getModuleIni('account_redirect_login');
        } else {
            $location = config::getModuleIni('account_default_url');
        }
        if (!$location) {
            $location = '/account/login/index';
            
        }
        return $location;
    }

    /**
     * get account from id
     * @param int $id
     * @return array $row
     */
    public function searchIdOrEmail ($id)  {        
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
    public function authFromMd5 ($md5) {
        $db = new db();
        $search = array ('md5_key' => $md5);
        
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
    
    public function getParentAccount ($id) {
        $db = new db();
        return $db->selectOne('account_sub', 'id', $id);
    }
    
    
    /**
     * gets a user row from emaill where account type is email
     * type defaults to 'email' in order to search for any type of
     * account use null
     * @param string $email
     * @param null|string $type defaults to email. Use NULL if you want to
     *                    fetch from any type of account. Or set type to e.g.
     *                    openid or facebook
     * @return array $row the user's account row if empty there is no user
     *                    with requested email
     */
    public function getUserFromEmail ($email, $type= 'email', $check_sub = null) {
        $db = new db();
        
        $search = array ();
        $search['email'] = strings_mb::tolower($email);
        if ($type) {
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
     * method for checking if a email exists in `account` table
     *
     * @param   string  $email the email to be checked for existens
     * @return  array|false  $row user row or false
     */
    public function emailExist($email){
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
            return array();
        }
    }

    /**
     * 
     * @param row $row
     * @return row $row
     */
    public function checkLocked ($row) {
        if ($row['locked'] == 1) {
            $this->errors['locked'] = lang::translate('This account has been locked'); 
            return array ();
        }
        return $row;
    }
    
    /**
     * compines check of locked account and check of verified account
     * @param array $row
     * @return array $row account row or empty row if user is not valid.
     */
    public function checkAccountFlags ($row) {
        if (empty($row)) { 
            return $row;
        }
        $row = $this->checkLocked($row);
        return $row;
    }

    
    /**
     * method setSessionAndCookie fires account_evetns
     * on method setSessionAndCookie
     * action 'account_login'
     * with arguments: account_login, user_id
     * 
     */
    public static function __events () {}
}

class account_module extends account {}

