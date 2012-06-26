<?php

moduleLoader::includeModule('account/login');
include_once "lightopenid/openid.php";
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
    }

    /**
     * method for showing a openid login form
     */
    public static function viewLoginForm(){
        echo templateView::get('account/lightopenid', 'login');
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
            $domain = get_main_ini('server_name');
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
                    header('Location: ' . $openid->authUrl());
                }

            } else if($openid->mode == 'cancel') {
                $this->errors[] = lang::translate('account_openid_login_cancelled');
                return false; 
            } else {               
                $this->status = lang::translate('account_openid_valid_login') . ' ' . htmlspecialchars($openid->identity). "<br />\n";
                if ($openid->validate()) {
                    $this->dispenseOpenid($openid);
                } else {
                    $this->errors[] = lang::translate('account_openid_invalid_login');
                }
                //echo 'User ' . ($openid->validate() ? $openid->identity . ' has ' : 'has not ') . 'logged in.';
            }
        } catch(ErrorException $e) {
            echo $e->getMessage();
        }
    }
    
    /**
     * creates a user in database
     * @param type $openid unique openid identifier
     */
    public function dispenseOpenid ($openid) {
        
        $account = $this->auth($openid->identity);
        if (isset($this->options['redirect'])) {
            $redirect = $this->options['redirect'];
        } else {
            $redirect = '/account/lightopenid/index';
        }
        
        if (!$account){
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
                
                // login events
                $args = array (
                    'action' => 'account_login',
                    'user_id' => $new,
                );
                
                event::getTriggerEvent(
                        config::getModuleIni('account_events'), 
                        $args);
            
                session::setActionMessage(
                    lang::translate('account_openid_loggedin_message'), true
                );
                
                //session_write_close();        
                account::redirectOnLogin($redirect);
             }

         // we got a row. use that for session
        } else {
            
            $this->setSesisonAndCookie($account);
            
            /*
            
            $_SESSION['id'] = $account['id'];
            $_SESSION['admin'] = $account['admin'];
            $_SESSION['super'] = $account['super'];
            $_SESSION['account_type'] = 'openid';
                            
            // set keep session
            if (isset($_SESSION['keep_session'])){
                session::setSystemCookie($account['id']);
            }
            
            // login events
            $args = array (
                    'action' => 'account_login',
                    'user_id' => $account['id'],
                );
                
            event::getTriggerEvent(
                config::getModuleIni('account_events'), 
                $args);
            
            */
            session::setActionMessage(
                lang::translate('account_openid_loggedin_message'), false
            );
            
            session_write_close();
            account::redirectOnLogin($redirect);                       
        }
    }

    /**
     * method for authorizing a user
     *
     * @param   string  username
     * @param   string  password
     * @return  array|0 row with user creds on success, 0 if not
     */
    public function auth ($openid){
        $db = new db();
        $search = array ('url' => $openid, 'type' => 'openid');
        $row = $db->selectOne('account', null, $search);
        return $row;
    }

    /**
     * method for creating a user
     *
     * @return int|false $res last_isnert_id on success or false on failure
     */
    public static function createUser ($openid){
        
        $ary = $openid->getAttributes();
        $db = new db();
        $values = array(
            'url'=> $openid->identity, 
            'email' => $ary['contact/email'],
            'type' => 'openid',
            'verified' => 1);
        
        if (isset($ary['namePerson/friendly'])) {
            $values['username'] = $ary['namePerson/friendly'];
        }
        
        $res = $db->insert('account', $values);
        if ($res) {
            return db::$dbh->lastInsertId();
        }
        return $res;
    }
}
