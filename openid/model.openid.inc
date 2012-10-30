<?php

moduleLoader::includeModule('account/login');

/**
 * class for doing login with openid
 */
class accountOpenid extends account {
    /**
     *
     * @var int telling if a user is logged in
     */
    public static $loggedIn = null;

    /**
     *
     * @var int userid
     */
    public static $id;

    /**
     *
     * @var array   holding errors
     */
    public static $errors = array();

    /**
     * set some basic messages
     */
    public static function  init() {
        // logout is the same for all login methods so far.
        if (session::isUser()){
            self::$id = session::getUserId();
        }
        $uri = uri::getInstance();
        if ($uri->fragment(3) == '1'){
            session::killSession();
            $redirect = config::getModuleIni('account_default_url');
            http::locationHeader($redirect);
        }

        if (isset($_SESSION['id'])) {
            self::$loggedIn = 1;
        }
    }

    /**
     * method for showing a openid login form
     */
    public static function viewLoginForm(){
        echo templateView::get('account/openid', 'login');
    }

   /**
    * method for creating a logout link
    */
    /*
    public static function viewLogout(){
        //accountLogin::setId();
        //accountLoginView::logout();
    }*/

    /**
     * method for login via openid. 
     */
    public static function login (){

        
        include_once "Zend/OpenId/Consumer.php";
        include_once "Zend/OpenId.php";
        include_once "Zend/OpenId/Extension/Sreg.php";
        include_once "Ak33m/OpenId/Consumer.php";

                //self::checkSystemCookie();
        /**
         * Sreg is used for getting some info from openid. 
         */       
        $sreg = new Zend_OpenId_Extension_Sreg(array(
                    'nickname'=>false,
                    'email'=>true,
                    'fullname'=>false), null, 1.1);
        
        if (@$_POST['keep_session'] == 1){
            //echo "we keep session";
            $_SESSION['keep_session'] = 1;
        }

        $status = "";

        if (isset($_POST['openid_action']) &&
            $_POST['openid_action'] == "login" &&
            !empty($_POST['openid_identifier'])) {
            if(filter_var($_POST['openid_identifier'], FILTER_VALIDATE_URL) === false){
                $error = lang::translate('account_no_valid_open_id_url');
                
            } else {
                $consumer = new Ak33m_OpenId_Consumer();
                // with sreg
                //if (!$consumer->login($_POST['openid_identifier'], null, null, $sreg)) {
                if (!$consumer->login($_POST['openid_identifier'], null, null, null)) {
                    $error = lang::translate('account_openid_login_failed');
                }
            }
        } else if (isset($_GET['openid_mode'])) {
            if ($_GET['openid_mode'] == "id_res") {
                $consumer = new Ak33m_OpenId_Consumer();
                // with sreg
                //if ($consumer->verify($_GET, $id, $sreg)) {
                if ($consumer->verify($_GET, $id)) {
                    $data = $sreg->getProperties();
                    //print_r($data); die;
                    $status = lang::translate('account_openid_valid_login') . ' ' . htmlspecialchars($id). "<br />\n";
                    $account = self::auth($id);

                    // create openid as user if we don't have one in existenz
                    if (!$account){
                        $new = self::createUser($id);
                        if ($new){
                            // use new id as user id for session
                            $status.=lang::translate('account_openid_account_created');
                            $_SESSION['id'] = $new;
                            $_SESSION['account_type'] = 'openid';

                            session::setActionMessage(
                                $status . "<br />" .
                                lang::translate('account_openid_loggedin_message')
                            );

                            account::redirectOnLogin('/account/openid/index');

                        }

                    // we got a row. use that for session
                    } else {
                        $_SESSION['id'] = $account['id'];
                        $_SESSION['admin'] = $account['admin'];
                        $_SESSION['super'] = $account['super'];
                        $_SESSION['account_type'] = 'openid';
                            
                        // set keep session
                        if (isset($_SESSION['keep_session'])){
                            session::setSystemCookie($account['id']);
                        }

                        session::setActionMessage(lang::translate('account_openid_loggedin_message'));
                        account::redirectOnLogin('/account/openid/index');                       
                    }
                } else {
                    $error = lang::translate('account_openid_invalid_login') . ' ' .  htmlspecialchars($id);
                }
            } else if ($_GET['openid_mode'] == "cancel") {
                $error = lang::translate('account_openid_login_cancelled');
            }
        }

        if (isset($error)){
            view_form_errors($error);
        } else {
            echo $status;
        }
    }

    /**
     * method for authorizing a user
     *
     * @param   string  username
     * @param   string  password
     * @return  array|0 row with user creds on success, 0 if not
     */
    public static function auth ($openid){
        $db = new db();
        try {
            //$password = md5($password);
            $search = array ('url' => $openid);
            $row = $db->selectOne('account', null, $search);
            if (empty($row)){
                return 0;
            } else {
                return $row;
            }
        } catch (PDOException $e) {

            $db>fatalError($e->getMessage());
        }
    }

    /**
     * method for creating a user
     *
     * @return int      1 success or 0 on failure
     */
    public static function createUser ($openid){
        $db = new db();
        
        $values = array('url'=> $openid, 'username' => $openid);
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