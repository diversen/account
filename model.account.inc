<?php

/**
 * File containing main account class with a few shared 
 * methods between different login methods. 
 */

template::setInlineCss(
        config::getModulePath('account') .  '/assets/account.css');

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
    public $options = array ();
    
    /**
     * status to give on login
     * @var string $str
     */
    public $status = '';
    
    public function __construct($options = array ()) {
        $this->options = $options;
    }
    
    /**
     * checks if a logout has taken place 
     */
    public function checkLogout () {
        $logout = uri::getInstance()->fragment(3);
        if ($logout == '1'){
            $this->doLogout();
        }
    }
    
    /**
     * logout 
     */
    public function doLogout () {
        
        $app_id = config::getModuleIni('account_facebook_api_appid'); //$facebook->getAppId()
        $server_name = config::getMainIni('server_name');

        setcookie('fbm_'.$app_id, '', time()-100, '/', $server_name);
        setcookie('fbsr_'.$app_id, '', time()-100, '/', $server_name); 
        //setcoo
        session::killSession();

        session_regenerate_id(true);

        $_SESSION=array();
        $redirect = config::getModuleIni('account_default_url');
        if (isset($this->options['redirect_logout'])) {
            $redirect = $this->options['redirect_logout'];
        }
        http::locationHeader($redirect);
    }
    
    /**
     * sets session and system cookie on login
     * we know user is authenticated and all we need is to set
     * the SESSION and system cookies.
     * 
     * @param array $account
     */
    public function setSesisonAndCookie ($account) {
        $_SESSION['id'] = $account['id'];
        $_SESSION['admin'] = $account['admin'];
        $_SESSION['super'] = $account['super'];
        $_SESSION['account_type'] = 'email';

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
     * method setSessionAndCookie fires account_evetns
     * on method setSessionAndCookie
     * action 'account_login'
     * with arguments: account_login, user_id
     * 
     */
    public static function __events () {}
    
   /**
    * method for creating a logout link. Fetch info from
    * a profile to show how to display logut
    */
    public static function displayLogout(){
        $row = user::getAccount(session::getUserId()); 
        echo user::getLogoutHTML($row);
    }

    /**
     * Method for redireting to default login url. The default URL is set 
     * in the ini setting 'account_default_url'
     */
    public static function redirectDefault (){
        $redirect = config::getModuleIni('account_default_url');
        http::locationHeader ($redirect);
    }

    /**
     * checks if we need to redirect to aspecified URL on login
     * examines latest $_SESSION['redirect_on_login']
     * @param string $url 
     */
    public static function redirectOnLogin ($url = null){
        if (isset($_SESSION['redirect_on_login'])){
            $redirect = $_SESSION['redirect_on_login'];
            unset($_SESSION['redirect_on_login']);
            http::locationHeader ($redirect);
        } else {
            if ($url){
                $location = $url;
            } else {
                $location = config::getModuleIni('account_default_url');
            }
            http::locationHeader($location);
         }
    }
    
        
    public function setAcceptNonVerifiedAccount () {
        $this->options['verified'] = false;
    }
}
