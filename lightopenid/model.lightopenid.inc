<?php

moduleLoader::includeModule('account/login');
include_once "lightopenid/openid.php";
view::includeOverrideFunctions('account', 'lightopenid/views.phtml');
/**
 * class for doing login with openid
 */
class accountLightopenid extends account {
    
    /**
     *
     * @var int telling if a user is logged in
     */

    function __construct($options = array ()) {
        $this->options = $options;
        
        // default login redirect
        if (!isset($this->options['redirect'])) {
            $this->options['redirect'] = '/account/lightopenid/index';
        }
    }

    /**
     * method for showing a openid login form
     */
    public static function viewLoginForm(){
        view_lightopenid_login ();
    }

    /**
     * method for login via openid. 
     */
    public function login (){

        $this->checkLogout();
        if (isset($_GET['keep_session']) && $_GET['keep_session'] == 1){
            $_SESSION['keep_session'] = 1;
        }

        try {
            $domain = config::getMainIni('server_name');
            $openid = new LightOpenID($domain);
            
            
            if(!$openid->mode) {
                if (isset($this->options['openid_identifier'])) {
                    $_GET['openid_identifier'] = $this->options['openid_identifier'];
                }
                
                if(isset($_GET['openid_identifier'])) {
                    $openid->identity = $_GET['openid_identifier'];
                    $openid->required = array(
                        'nickname', 
                        'namePerson/friendly', 
                        'contact/email');
                    http::locationHeader($openid->authUrl());
                }

            } else if($openid->mode == 'cancel') {
                $this->errors[] = lang::translate('account_openid_login_cancelled');
                return false; 
            } else {               
                $this->status = lang::translate('account_openid_valid_login') . ' ' . htmlspecialchars($openid->identity). "<br />\n";
                if ($openid->validate()) {
                    $res = $this->dispenseOpenid($openid);
                    if (!$res) {
                        return false;
                    }
                } else {
                    $this->errors[] = lang::translate('account_openid_invalid_login');
                    return false;
                }
                //echo 'User ' . ($openid->validate() ? $openid->identity . ' has ' : 'has not ') . 'logged in.';
            }
        } catch(ErrorException $e) {
            log::error($e->getMessage());
            return false;
        }
    }
    
 
    
    /**
     * creates a user in database
     * @param type $openid unique openid identifier
     */
    public function dispenseOpenid ($openid) {
        
        $account = $this->auth($openid->identity);
        if (empty($account)){
            $new = $this->createUser($openid);
            if ($new){
                // use new id as user id for session
                //$status =lang::translate('account_openid_account_created');
                
                $_SESSION['id'] = $new;
                $_SESSION['account_type'] = 'openid';
                
                // create events
                $args = array (
                    'action' => 'account_create',
                    'user_id' => $new,
                );
                
                event::getTriggerEvent(
                        config::getModuleIni('account_events'), 
                        $args);
            
                $account = user::getAccount($new);
                $this->setSesisonAndCookie($account);
                
                session::setActionMessage(
                    lang::translate('account_openid_loggedin_message'), true
                );
                      
                account::redirectOnLogin($this->options['redirect']);
             } else {
                 return false;
             }
        } else {
            
            $this->setSesisonAndCookie($account);
            session::setActionMessage(
                lang::translate('account_openid_loggedin_message'), false
            );
            
            account::redirectOnLogin($this->options['redirect']);                       
        }
    }
    
    /**
     * under normal mode we accept any identifier. 
     * Use this if you only want to allow a single identifier
     * @param string $identifier, e.g. 'https://www.google.com/accounts/o8/id'
     */
    public function setOpenidIdentifier ($identifier) {
        $this->options['openid_identifier'] = $identifier;
    }
    
       /**
     * connect a open id to a parent account
     * @return boolean
     */
    public function connect () {

        try {
            $domain = config::getMainIni('server_name');
            $openid = new LightOpenID($domain);
            $user_id = session::getUserId();
            if (empty($user_id)) {
                return false;
            }
            
            if(!$openid->mode) {
                if (isset($this->options['openid_identifier'])) {
                    $_GET['openid_identifier'] = $this->options['openid_identifier'];
                }
                
                if(isset($_GET['openid_identifier'])) {
                    $openid->identity = $_GET['openid_identifier'];
                    $openid->required = array(
                        'nickname', 
                        'namePerson/friendly', 
                        'contact/email');
                    http::locationHeader($openid->authUrl());
                }

            } else if($openid->mode == 'cancel') {
                $this->errors[] = lang::translate('account_openid_login_cancelled');
                return false; 
            } else {               
                $this->status = lang::translate('account_openid_valid_login') . ' ' . htmlspecialchars($openid->identity). "<br />\n";
                if ($openid->validate()) {
                    $this->connectOpenidAccount($openid, $user_id);
                } else {
                    $this->errors[] = lang::translate('account_openid_invalid_login');
                }
                //echo 'User ' . ($openid->validate() ? $openid->identity . ' has ' : 'has not ') . 'logged in.';
            }
        } catch(ErrorException $e) {
            log::error($e->getMessage());
            return false;
        }
    }
    
    /**
     * creates a user in database
     * @param type $openid unique openid identifier
     */
    public function connectOpenidAccount ($openid, $user_id) {
        $exists = $this->openIdExists($openid->identity);
        if ($exists) {
            $this->errors[] = 'Open id exists in other user account';
            return false;
        }
        
        $account = $this->auth($openid->identity);
        if (empty($account)){
            $new = $this->createUserSub($openid, $user_id);

            if ($new){

                $args = array (
                    'action' => 'account_connect',
                    'user_id' => $user_id,
                );
                
                event::getTriggerEvent(
                    config::getModuleIni('account_events'), 
                    $args);
                
                
                
             }
             account::redirectOnLogin($this->options['redirect']);

         // we got a row. user has connect his open id account
        } else {

            account::redirectOnLogin($this->options['redirect']);                       
             
        }
    }
    
    /**
     * method for authorizing a user
     *
     * @param   string  username
     * @param   string  password
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function openIdExists ($openid){
        
        // first check for a sub account and return parent account
        $db = new db();
        $search = array ('url' => $openid, 'type' => 'openid');
        $row = $db->selectOne('account_sub', null, $search);
        if (!empty($row)) {
            return true;
        } 
        
        // check main account
        $search = array ('url' => $openid, 'type' => 'openid');
        $row = $db->selectOne('account', null, $search);
        if (!empty($row)) {

            return true;
        } 
        
        
        return false;
    }
    

    /**
     * method for authorizing a user
     *
     * @param   string  username
     * @param   string  password
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function auth ($openid){
        
        // first check for a sub account and return parent account
        $db = new db();
        $search = array ('url' => $openid, 'type' => 'openid');
        $row = $db->selectOne('account_sub', null, $search);
        if (!empty($row)) {
            $row = $db->selectOne('account', null, array ('id' => $row['parent']));
            return $row;
        } 
        
        // check main account
        $search = array ('url' => $openid, 'type' => 'openid');
        $row = $db->selectOne('account', null, $search);
        return $row;
    }

    /**
     * method for creating a user
     *
     * @return int|false $res last_isnert_id on success or false on failure
     */
    public function createUser ($openid){
        
        $ary = $openid->getAttributes();
        $db = new db();
        
        if (config::getModuleIni('account_needs_email')) {
            if (!isset($ary['contact/email']) || empty($ary['contact/email'])) {
                $this->errors[] = lang::translate('account: openid: no valid email');
                return false;
            }
        } 
       
        
        if (isset($this->options['unique_email'])) {
            if ($this->emailExist($ary['contact/email'])) {
                $this->errors[] = lang::translate('account: openid: email already exists');
                return false;
            }
        }
        
        $md5_key = random::md5();
        $values = array(
            'url'=> $openid->identity, 
            'email' => $ary['contact/email'],
            'type' => 'openid',
            'verified' => 1,
            'md5_key' => $md5_key);
        
        // If not isset options verified - we allow non verified account to log in
        if (isset($this->options['verified']) && !$this->options['verified']) {
            unset($values['verified']);
        }
        
        if (isset($ary['namePerson/friendly'])) {
            $values['username'] = $ary['namePerson/friendly'];
        }
        
        $res = $db->insert('account', $values);
        if ($res) {
            return db::$dbh->lastInsertId();
        }
        return $res;
    }
    
    /**
     * method for creating a sub user
     *
     * @return int|false $res last_isnert_id on success or false on failure
     */
    public function createUserSub ($openid, $user_id){
        
        $db = new db();
        $row = $db->selectOne('account_sub', 'url', $openid->identity);
        if (!empty($row)) {
            return array ();
        }
        
        $ary = $openid->getAttributes();
        
        $values = array(
            'url'=> $openid->identity, 
            'email' => $ary['contact/email'],
            'type' => 'openid',
            'verified' => 1,
            'parent' => $user_id);
        
        // If not isset options verified - we allow non verified account to log in
        if (isset($this->options['verified']) && !$this->options['verified']) {
            unset($values['verified']);
        }
        
        if (isset($ary['namePerson/friendly'])) {
            $values['username'] = $ary['namePerson/friendly'];
        }
        
        $res = $db->insert('account_sub', $values);
        if ($res) {
            return db::$dbh->lastInsertId();
        }
        return $res;
    }
    
    /**
     * account_events (set in account_events)
     * 
     * method dispenseOpenid fires actions:
     * 
     *      account_create 
     *      account_login
     * 
     * arguments to the event methods are
     * 
     *      action,
     *      user_id,
     * 
     */
    public static function __events () {}
}
