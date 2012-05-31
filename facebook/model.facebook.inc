<?php

require 'facebook.php';
moduleLoader::includeModule('account/login');

class accountFacebook {
    /**
     *
     * @var boolean $loggedIn tells if user is logged in or not
     */
    public static $loggedIn = null;

    /**
     *
     * @var int $id the users id. 
     */
    public static $id;

    /**
     *
     * @var array $errors array holding errors
     */
    public static $errors = null;

    /**
     * init facebook
     * checks if user is logged in
     * checks if session needs to be killed (logging out)
     * @return boolean $res true if user is logged in else false
     */
    public static function  init() {
        // logout is the same for all login methods so far.
        $uri = uri::getInstance();
        if ($uri->fragment(3) == '1'){
            session_destroy();
            return false;
        }
        
        if (session::isUser()){
            self::$loggedIn = 1;
            self::$id = session::getUserId();
            return true;
        }
    }
    
    /**
     * method for getting the facebook 'next' param - the url to
     * redirect to when logging out. 
     * @return string $str the logout url
     */
    public static function getLogoutNext () {
        $server = get_main_ini('server_name');
        return "http://$server/account/facebook/logout";
    }


    /**
     * method for authorizing a user
     * @param   string  $facebook_url
     * @return  array $row with user creds on success, empty if no user
     */

    public function auth ($facebook_url){
        $db = new db();
        $search = array ('url' => $facebook_url);
        $row = $db->selectOne('account', null, $search);
        return $row;
    }

    /**
     * method for creating a user
     * @return int $res positive int on success or 0 on failure
     */
    public function createUser ($user){
        $db = new db();     
        $values = array(
            'url'=> $user['link'], 
            'username' => $user['name'],
            'email' => $user['email']
            );
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
            $str .= lang::translate('account_logged_in_with_email') . ": <br />" . $row['email'];
        } else {
            $str .= lang::translate('account_logged_in_with_facebook') . ": <br />" . $row['url'];

        }
        $str .= "<hr />" . html::createLink($logoutUrl, lang::translate('account_logout'));
        view_confirm($str);
    }
}
