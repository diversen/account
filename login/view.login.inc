<?php

/**
 * @package    account
 */

/**
 * function for creating a login form
 */

class accountLoginView {

    /**
     *
     * @var string  string holding extensions html to add to login.
     */
    public static $accountLoginHTML = '';
    /**
     * method for displayng default login box
     */
    public static function login (){
        
        if (!isset($_POST['submit'])){
            $init = array ('keep_session' => 'on');
        } else {
            $init = $_POST;
        }
        
        html::$autoLoadTrigger = 'submit';
        html::init($init);
        html::formStart('account_request_new_password');
        html::legend(lang::translate('account_login_legend'));
        html::label('email', lang::translate('account_email_label'), array('required' => true));
        html::text('email');
        //html::label('captcha', captcha::createCaptcha());
        //html::text('captcha');
        html::label('password', lang::translate('account_form_password'), array('required' => true));
        html::password('password');
        
        $keep_session_label = lang::translate('account_keep_session') . ' ' . config::getMainIni('cookie_time') . ' ' . lang::translate('account_num_days');
        html::label('keep_session', $keep_session_label);
        html::checkbox('keep_session');
        
        html::label('submit', '');
        html::submit('submit', lang::translate('account_submit'));
        html::formEnd();
        echo html::getStr();

?>

<?php

    if (isset(self::$accountLoginHTML)){
            echo self::$accountLoginHTML;
        }
    }

   /**
    * method for creating a logout link
    */
    public static function logout(){

        accountLogin::setId();
        $row = accountLogin::getUser();

        $str = '';
        $logout_url = "/account/login/index/1";

        // only default account use email as a verification
        if (!empty($row['email'])){
            $str.= lang::translate('account_logged_in_with_email');
            $str.= MENU_SUB_SEPARATOR_SEC . "<br />" . $row['email'] . ' ';
            $str.= "(" . lang::translate('account_login_will_not_be_displayed') . ")";
        } else {
            $str.= lang::translate('account_logged_in_with_alternative');
            $str.= MENU_SUB_SEPARATOR_SEC . "<br />" . $row['url'] . ' ';
            $str.= "(" . lang::translate('account_login_will_not_be_displayed') . ")";
            // use facebook api
            if (strstr($row['url'], 'facebook')){
                header("Location: /account/facebook/index/1");           
            }
        }

        $str.= '<br />';
        $profile_info = user::getProfileInfo($row);

        if (empty($profile_info['screenname'])) {
            $str.= lang::translate('account_no_screenname');
            
        } else {
            $str.= lang::translate('account_your_screenname_is') . MENU_SUB_SEPARATOR_SEC . $profile_info['screenname'];
        }

        $str.='<br />';
        $str.= user::getProfileEditLink($row['id']);
        $str .= "<hr />" . html::createLink($logout_url, lang::translate('account_logout'));
        view_confirm($str);
    }

    /**
     *
     * Use this function to add html to the normal login box.
     *
     * @param string $html
     */

    public static function setAccountLoginHTML ($html){
        self::$accountLoginHTML.= $html;
    }
}