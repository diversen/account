<?php

moduleLoader::includeModule('account/login');
include_once "lightopenid/openid.php";
/**
 * class for doing login with openid
 */
class accountLightopenid {
    /**
     *
     * @var int telling if a user is logged in
     */
    public $loggedIn = null;

    /**
     *
     * @var int userid
     */
    public $id;

    /**
     *
     * @var array   holding errors
     */
    public $errors = array();

    /**
     * set some basic messages
     */
    public function  init() {
        // logout is the same for all login methods so far.
        if (session::isUser()){
            $this->id = session::getUserId();
        }
        
        $uri = uri::getInstance();
        if ($uri->fragment(3) == '1'){
            session::killSession();
            $redirect = config::getModuleIni('account_default_url');
            header( "Location: $redirect");
            die;
        }

        if (isset($_SESSION['id'])) {
            $this->loggedIn = 1;
        }
    }

    /**
     * method for showing a openid login form
     */
    public static function viewLoginForm(){
        echo templateView::get('account/lightopenid', 'login');
    }

   /**
    * method for creating a logout link
    */
    public static function viewLogout(){
        accountLogin::setId();

    }

    /**
     * method for login via openid. 
     */
    public function login (){

        if (@$_GET['keep_session'] == 1){
            $_SESSION['keep_session'] = 1;
        }

        $status = '';
        try {
            $domain = get_main_ini('server_name');
            $openid = new LightOpenID($domain);
            if(!$openid->mode) {
                if(isset($_GET['openid_identifier'])) {
                   
                    $openid->identity = $_GET['openid_identifier'];
                    $openid->required = array(
                        'nickname', 
                        'namePerson/friendly', 
                        'contact/email');
                    header('Location: ' . $openid->authUrl());
                }

            } elseif($openid->mode == 'cancel') {
                $error = lang::translate('account_openid_login_cancelled');
            } else {
                
                $status = lang::translate('account_openid_valid_login') . ' ' . htmlspecialchars($openid->identity). "<br />\n";
                if ($openid->validate()) {
                    $this->dispenseOpenid($openid);
                } else {
                    $error = lang::translate('account_openid_invalid_login');
                }
                echo 'User ' . ($openid->validate() ? $openid->identity . ' has ' : 'has not ') . 'logged in.';
            }
        } catch(ErrorException $e) {
            echo $e->getMessage();
        }


        if (isset($error)){
            view_form_errors($error);
        } else {
            echo $status;
        }
    }
    
    public function dispenseOpenid ($openid) {
        $account = $this->auth($openid->identity);

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
                
                session_write_close();
                accountLogin::redirectOnLogin('/account/lightopenid/index');
             }

         // we got a row. use that for session
        } else {
            
            session::setActionMessage(
                lang::translate('account_openid_loggedin_message'), false
            );
            
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
            
            session_write_close();
            accountLogin::redirectOnLogin('/account/lightopenid/index');                       
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
        $search = array ('url' => $openid);
        $row = $db->selectOne('account', null, $search);
        return $row;

    }

    /**
     * method for creating a user
     *
     * @return int      1 success or 0 on failure
     */
    public static function createUser ($openid){
        //contact/email
        
        $ary = $openid->getAttributes();
        
        $db = new db();
        $values = array(
            'url'=> $openid->identity, 
            'email' => $ary['contact/email']);
        
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
     *
     * @return mixed    user row or 0
     */
    public static function getUser(){
        $db = new db();
        try {
            $row = $db->selectOne('account', 'id', self::$id);           
            return $row;
        } catch (PDOException $e) {
            $db->fatalError($e->getMessage());
        }
        return 0;
    }
}