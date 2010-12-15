<?php

include_once _COS_PATH . '/modules/account/facebook/src/facebook.php';
include_module('account/login');

/**
 * class for doing login with openid
 */
class accountFacebook {
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
            //$redirect = get_module_ini('account_default_url');
            //header( "Location: $redirect");
            //echo "die";
            //die;
        }

        if (isset($_SESSION['id'])) {
            self::$loggedIn = 1;
        }
    }


    /**
     * method for login via openid. 
     */
    public static function login (){
                
        if (@$_POST['keep_session'] == 'on'){
            //echo "we keep session";
            $_SESSION['keep_session'] = 1;
        }

        $status = "";

        
    }

    /**
     * method for authorizing a user
     *
     * @param   string  username
     * @param   string  password
     * @return  array|0 row with user creds on success, 0 if not
     */
    public static function auth ($facebook_url){
        $db = new db();

        $search = array ('url' => $facebook_url);
        $row = $db->selectOne('account', null, $search);
        if (empty($row)){
            return 0;
        } else {
            return $row;
        }
    }

    /**
     * method for creating a user
     *
     * @return int      1 success or 0 on failure
     */
    public static function createUser ($facebook_url){
        $db = new db();
        
        $values = array('url'=> $facebook_url, 'username' => $facebook_url);
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
        $row = $db->selectOne('account', 'id', self::$id);
        if (!empty($row)){
            return $row;
        }
        return 0;
    }
    
    /**
    * method for creating a logout link
    */
    public static function logout($logoutUrl = null){

        
        $row = accountLogin::getUser();

        $str = '';
        if (!empty($row['email'])){
            $str .= lang::translate('logged_in_with_email') . ": <br />" . $row['email'];
        } else {
            $str .= lang::translate('logged_in_with_facebook') . ": <br />" . $row['url'];

        }
        $str .= "<hr />" . create_link($logoutUrl, lang::translate('Logout'));
        //$str .= "<br />" . create_link("/account/login/index/1", lang::translate('Logout'));
        view_confirm($str);
    }
}