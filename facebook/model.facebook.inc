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
     */
    public static function  init() {
        // logout is the same for all login methods so far.
        $uri = uri::getInstance();
        if ($uri->fragment(3) == '1'){
            session_destroy();
            //session::killSession();
            return false;
        }
        
        if (session::isUser()){
            self::$loggedIn = 1;
            self::$id = session::getUserId();
            return true;
        }
    }
    
    public static function getLogoutNext () {
        $server = get_main_ini('server_name');
        return "http://$server/account/facebook/logout";
    }


    /**
     * method for authorizing a user
     *
     * @param   string  $facebook_url
     * @return  array|0 $row with user creds on success, 0 if not
     */

    public function auth ($facebook_url){
        $db = new db();

        $search = array ('url' => $facebook_url);
        $row = $db->selectOne('account', null, $search);
        return $row;
    }

    /**
     * method for creating a user
     *
     * @return int      1 success or 0 on failure
     */
    public function createUser ($facebook_url){
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
            $str .= lang::translate('account_logged_in_with_email') . ": <br />" . $row['email'];
        } else {
            $str .= lang::translate('account_logged_in_with_facebook') . ": <br />" . $row['url'];

        }
        $str .= "<hr />" . html::createLink($logoutUrl, lang::translate('account_logout'));
        view_confirm($str);
    }
}

function getFacebookObj () {

    $facebook = new Facebook(array(
        'appId'  => config::getModuleIni('account_facebook_api_appid'),
        'secret' => config::getModuleIni('account_facebook_api_secret'),    
    ));
    
    return $facebook;
}


function getFacebookLoginLink () {
    $facebook = getFacebookObj();
    $loginUrl = $facebook->getLoginUrl();
    return html::createLink($loginUrl, "Login");
}

function getFacebookUser () {
    $facebook = getFacebookObj();
    $user = $facebook->getUser();
    return $user;
}

function getFacebookProfile () {
    $facebook = getFacebookObj();
    //if ($user) {
        try {
            // Proceed knowing you have a logged in user who's authenticated.
            $user_profile = $facebook->api("/me");
        } catch (FacebookApiException $e) {
            echo "exp: no profile";
            print_r($e);
            $user_profile = null;
        }
  //  }
    return $user_profile;
}