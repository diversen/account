<?php

use diversen\conf;
use diversen\db;
use diversen\event;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\log;
use diversen\moduleloader;
use diversen\random;
use diversen\session;
use diversen\strings\mb;
use diversen\template;
use diversen\user;
use diversen\view;


moduleloader::includeModule('account/login');
if (in_array('lightopenid', conf::getModuleIni('account_logins'))) {
    include_once "vendor/iignatov/lightopenid/openid.php";
    view::includeOverrideFunctions('account', 'lightopenid/views.php');
}

/**
 * class for doing login with openid
 */
class account_lightopenid extends account {
    
    /**
     *
     * @var int telling if a user is logged in
     */

    function __construct($options = array ()) {
        
        $this->options = $options;
    }
    
    public function indexAction() {
        usleep(100000);
        template::setTitle(lang::translate('OpenID login'));

// check to see if user is allowed to use lightopenid
        if (!in_array('lightopenid', conf::getModuleIni('account_logins'))) {
            moduleloader::setStatus(403);
            return;
        }

        $options = array();
        if (isset($_GET['keep_session']) && $_GET['keep_session'] == 1) {
            $_SESSION['keep_session'] = 1;
        }

        if (isset($_SESSION['keep_session'])) {
            $options['keep_session'] = 1;
        }

        $options['unique_email'] = true;
        $this->options = $options;
        if (!session::isUser()) {
            $this->login();
            if (!empty($l->status)) {
                echo $l->status;
            }
            if (!empty($l->errors)) {
                echo html::getErrors($l->errors);
            }
            account_lightopenid_views::loginForm();
            echo account_views::getTermsLink();
        } else {
            $a = new account_login();
            $a->displayLogout();
        }
    }

    /**
     * method for showing a openid login form
     */
    public function viewLoginForm(){
        account_lightopenid_views::loginForm ();
    }

    /**
     * method for login via openid. 
     */
    public function login (){

        if (isset($_GET['keep_session']) && $_GET['keep_session'] == 1){
            $_SESSION['keep_session'] = 1;
        }

        try {
            $domain = conf::getMainIni('server_name');
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
                $this->errors[] = lang::translate('OpenID Login was cancelled');
                return false; 
            } else {               
                $this->status = lang::translate('OpenID login accepted') . ' ' . htmlspecialchars($openid->identity). "<br />\n";

                if ($openid->validate()) {
                    $res = $this->dispenseOpenid($openid);
                    if (!$res) {
                        $this->errors[] = lang::translate('Could not dispense OpenID');
                        return false;
                    }
                } else {
                    $this->errors[] = lang::translate('Invalid openID');
                    return false;
                }
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
        
        // check both account and account_sub
        $account = $this->auth($openid->identity);
        
        if (empty($account)){
            $new = $this->createUser($openid);
            if ($new){
            
                $account = user::getAccount($new);
                $this->setSessionAndCookie($account, 'openid');
                
                session::setActionMessage(
                    lang::translate('Logged ind with openID'), true
                );
                      
                $this->redirectOnLogin($this->options['redirect']);
             } else {
                 return false;
             }
        } else {
            
            $this->setSessionAndCookie($account);
            session::setActionMessage(
                lang::translate('Logged ind with openID'), false
            );
            
            $this->redirectOnLogin($this->options['redirect']);                       
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
            $domain = conf::getMainIni('server_name');
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
                $this->errors[] = lang::translate('OpenID Login was cancelled');
                return false; 
            } else {               
                $this->status = lang::translate('OpenID login accepted') . ' ' . htmlspecialchars($openid->identity). "<br />\n";
                if ($openid->validate()) {
                    $this->connectOpenidAccount($openid, $user_id);
                } else {
                    $this->errors[] = lang::translate('Invalid openID');
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
                    conf::getModuleIni('account_events'), 
                    $args);
    
                
            }
             $this->redirectOnLogin($this->options['redirect']);

         // we got a row. user has connect his open id account
        } else {

            $this->redirectOnLogin($this->options['redirect']);                       
             
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
            $row = $this->checkAccountFlags($row);
            return $row;
        } 
        
        // check main account
        $search = array ('url' => $openid, 'type' => 'openid');
        $row = $db->selectOne('account', null, $search);
        $row = $this->checkAccountFlags($row);
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
        
        if (!isset($ary['contact/email']) || empty($ary['contact/email'])) {
            $this->errors['openid_email'] = lang::translate('Invalid login. We need a valid OpenID email');
            return false;
        }

        if (isset($this->options['unique_email'])) {
            $account = $this->getUserFromEmail($ary['contact/email'], null);
            if (!empty($account)) {
                $auto_merge = conf::getModuleIni('account_auto_merge');
                if ($auto_merge) {
                    $res = $this->autoMergeAccounts($openid, $account['id']);
                    if (!$res) {
                        $this->errors[] = lang::translate('Could not merge your account with main account. This may be an email account.');
                    }
                }
                
                // if no auto merge we set an error
                $this->errors['openid_email_exists'] = lang::translate('Email already exists in system');
                return false;
            }
        }
        
        $md5_key = random::md5();
        $values = array(
            'url'=> $openid->identity, 
            'email' => mb::tolower($ary['contact/email']),
            'type' => 'openid',
            'verified' => 1, // open id accounts are always verified
            'md5_key' => $md5_key);
        
        if (isset($ary['namePerson/friendly'])) {
            $values['username'] = $ary['namePerson/friendly'];
        }
        
        $res = $db->insert('account', $values);
        if ($res) {
            
            $id = $db->lastInsertId();
            
            // create events
            $args = array (
                'action' => 'account_create',
                'user_id' => $id,
            );
                
            event::getTriggerEvent(
                conf::getModuleIni('account_events'), 
                $args);
            
            return $id;
            
            
        }
        return $res;
    }
    
    /**
     * auto merge two accounts
     * @param objct $openid lightopenid object
     * @param int $user_id
     * @return int|false $parent_id main account id
     */
    public function autoMergeAccounts ($openid, $user_id) {
        
        // examine if we are allowed to merge this URL
        $allow_merge = conf::getModuleIni('account_auto_merge');
        $url = $openid->identity;
        $parts = parse_url($url);
        $host = $parts['host'];
        $res = false;
        foreach($allow_merge as $host) {
            if (in_array($host, $allow_merge)) {
                $res = true;
                break;
            }
        }
        
        if ($res) {
            $res_create = $this->createUserSub($openid, $user_id);
            if ($res_create) {
                
                // run account_connect events
                $args = array (
                    'action' => 'account_connect',
                    'user_id' => $user_id,
                );

                event::getTriggerEvent(
                    conf::getModuleIni('account_events'), 
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
    public function createUserSub ($openid, $user_id){
        
        $db = new db();
        $row = $db->selectOne('account_sub', 'url', $openid->identity);
        if (!empty($row)) {
            return array ();
        }
        
        $ary = $openid->getAttributes();
        
        $values = array(
            'url'=> $openid->identity, 
            'email' => mb::tolower($ary['contact/email']),
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
            return $db->lastInsertId();
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
