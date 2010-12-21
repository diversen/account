<?php

include_module('account/login');

/**
 * class for doing login with openid
 */
class accountOpenid {
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
    public static $errors = null;

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
            $redirect = get_module_ini('account_default_url');
            header( "Location: $redirect");
            die;
        }

        if (isset($_SESSION['id'])) {
            self::$loggedIn = 1;
        }
    }

    /**
     * method for showing a openid login form
     */
    public static function viewLoginForm(){

        $file = _COS_PATH . '/modules/account/openid/views/login.inc';
        $html = simple_template($file);
        echo $html;

    }

   /**
    * method for creating a logout link
    */
    public static function viewLogout(){
        accountLogin::setId();
        //accountLoginView::logout();
    }

    /**
     * method for login via openid. 
     */
    public static function login (){

        
        include_once "Zend/OpenId/Consumer.php";
        include_once "Zend/OpenId.php";
        include_once "Ak33m/OpenId/Consumer.php";

                //self::checkSystemCookie();
                
        if (@$_POST['keep_session'] == 'on'){
            //echo "we keep session";
            $_SESSION['keep_session'] = 1;
        }

        $status = "";

        if (isset($_POST['openid_action']) &&
            $_POST['openid_action'] == "login" &&
            !empty($_POST['openid_identifier'])) {
            if(filter_var($_POST['openid_identifier'], FILTER_VALIDATE_URL) === false){
                $error = lang::translate('no_valid_open_id_url');
                
            } else {
                $consumer = new Ak33m_OpenId_Consumer();
                if (!$consumer->login($_POST['openid_identifier'])) {
                    $status = lang::translate("openid_login_failed");
                }
            }
        } else if (isset($_GET['openid_mode'])) {
            if ($_GET['openid_mode'] == "id_res") {
                $consumer = new Ak33m_OpenId_Consumer();
                if ($consumer->verify($_GET, $id)) {
                    $status = lang::translate('openid_valid_login') . ' ' . htmlspecialchars($id). "<br />\n";
                    $account = self::auth($id);

                    // create openid as user if we don't have one in existenz
                    if (!$account){
                        $new = self::createUser($id);
                        if ($new){
                            // use new id as user id for session
                            $status.=lang::translate('openid_account_created');
                            $_SESSION['id'] = $new;
                            $_SESSION['account_type'] = 'openid';

                            session::setActionMessage(
                                $status . "<br />" .
                                lang::translate('openid_loggedin_message')
                            );

                            accountLogin::redirectOnLogin('/account/openid/index');

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

                        session::setActionMessage(lang::translate('openid_loggedin_message'));

                        accountLogin::redirectOnLogin('/account/openid/index');
                        
                    }
                } else {
                    $error = lang::translate('openid_invalid_login') . ' ' .  htmlspecialchars($id);
                }
            } else if ($_GET['openid_mode'] == "cancel") {
                $error = lang::translate('openid_login_cancelled');
            }
        }

        if (isset($error)){
            view_form_errors($error);
        } else {
            echo $status;
        }
    }

    public function loginGoogle (){
        
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