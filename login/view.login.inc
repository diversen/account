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

        if (isset($_POST['submit'])){
            if (isset($_POST['keep_session'])){
                $keep_session = 'checked="checked"';
            } else {
                $keep_session = '';
            }
        } else {
            $keep_session = 'checked="checked"';
        }

?>
<div id="form_main">
<form method="post" action="">
<fieldset>
<legend>Login</legend>
<label for="email"><?php echo lang::translate('Email')?>:</label><br />
<input type="text" name="email" value="<?=@htmlentities($_POST['email'])?>"><br />
<label for="password"><?php echo lang::translate('Password')?>:</label><br />
<input type="password" name="password" value="<?=@htmlentities($_POST['password'])?>"><br />


<label for="keep_session"><?=lang::translate('keep_session')?> <?=get_main_ini('cookie_time')?> <?=lang::translate('days')?></label><br />
<input name="keep_session" <?=$keep_session?> type="checkbox">

<label for="submit"></label><br />
<input type="submit" name="submit" value="<?php echo lang::translate('Submit')?>"><br />
</fieldset>
</form>
</div>
<?php

    if (isset(self::$accountLoginHTML)){
            print self::$accountLoginHTML;
        }
    }

   /**
    * method for creating a logout link
    */
    public static function logout(){

        $row = accountLogin::getUser();
        $str = '';
        $logout_url = "/account/login/index/1";

        // only default account use email as a verification
        if (!empty($row['email'])){
            $str .= lang::translate('logged_in_with_email') . ": <br />" . $row['email'];
        } else {
            $str .= lang::translate('logged_in_with_alternative') . ": <br />" . $row['url'];
            // facebook special. We need to create speciel link when logging out with facebook
            if (strstr($row['url'], 'facebook')){
                // facebook login
                header("Location: /account/facebook/index");
            }
        }
        $str .= "<hr />" . create_link($logout_url, lang::translate('Logout'));
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