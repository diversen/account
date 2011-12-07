<?php

/**
 * File containing class for srequesting and etting a new user password
 * 
 */

include_once "captcha.php";

/**
 * class requestpw 
 */
class requestpw {

    /**
     *
     * @var array $errors
     */
    public $errors = array();


    /**
     * method for sanitizing input. 
     */
    public function sanitize (){
        $_POST = html::specialEncode($_POST);
    }

    /**
     * method for validating input
     */
    function validate(){
        if (!captcha::checkCaptcha(trim($_POST['captcha']))){
            $this->errors['captcha'] = lang::translate('account_error_incorrect_captcha');
        }

        $db = new db();
        $row = $db->selectOne('account', 'email', $_POST['email']);
        if (empty($row)){
            $this->errors['email'] = lang::translate('account_error_no_such_email');
            return 0;
        }
    }

    /**
     * method for requesting password
     *
     * @return int  $res 1 on succes and 0 on failure
      */
    public function requestPassword (){   
        $md5_key = md5(uniqid(rand(), true));

        
        $db = new db();
        $row = $db->selectOne('account', 'email', $_POST['email']);
        
        $values = array('md5_key' => $md5_key);
        $db->update('account', $values, $row['id']);
        
        $vars['site_name'] = 'http://' . $_SERVER['HTTP_HOST'];
        $subject = lang::translate('account_request_pw_subject_for_site') . " " . $vars['site_name'];

        $vars['verify_key'] = "$vars[site_name]/account/requestpw/verify/$row[id]/$md5_key";
   
        $lang = get_main_ini('language');        
        $view_file = _COS_PATH . "/modules/account/lang/$lang/request_password.inc";
        $message = get_include_contents($view_file, $vars);

        $from = register::$vars['coscms_main']['site_email'];

        if (mail_utf8($row['email'], $subject, $message, $from)){
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * method for setting a new password
     *
     * @return boolean $res true on success and false on failure
     */
    public function setNewPassword(){
        $uri = uri::getInstance();
        $id = $uri->fragment(3);
        $md5_key = $uri->fragment(4);
        $search = array ('id' => $id, 'md5_key' => $md5_key);
        
        $db = new db();
        
        $row = $db->selectOne('account', null, $search);
        $md5_key = md5(uniqid(rand(), true));
        
        $_POST = html::specialDecode($_POST);
        $values = array('password' => md5($_POST['password1']), 'md5_key' => $md5_key);
        $res = $db->update('account', $values, $row['id']);
        return $res;
    }

    /**
     * method for validating password
     * sets errors if any
     */
    public function validatePassword(){
        if (strlen($_POST['password1']) < 7){
            echo "argh";
            $this->errors['password'] = lang::translate('account_password_length_error');
        }
        if ($_POST['password1'] != $_POST['password2']){
            $this->errors['password'] = lang::translate('account_password_no_match');
        }
    }

    /**
     * method for verifying account from link sent in email
     *
     * @return boolean  true on success or false on failure
     */
    public function verifyAccount(){
        $uri = uri::getInstance();
        $id = $uri->fragment(3);
        $md5_key = $uri->fragment(4);
        $search = array ('id' => $id, 'md5_key' => $md5_key);

        $db = new db();
        $row = $db->selectOne('account', null, $search);

        if (empty($row)){
            $this->errors[] = lang::translate('account_request_no_combination_md5_user');
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * Displays a form for requesting a new password. 
     */
    public function displayRequest () {

        html::$autoLoadTrigger = 'submit';
        html::init($_POST);
        html::formStart('account_request_new_password');
        html::legend(lang::translate('account_request_password_title'));
        html::label('email', lang::translate('account_email_label'));
        html::text('email');
        html::label('captcha', captcha::createCaptcha());
        html::text('captcha');
        html::label('submit', '');
        html::submit('submit', lang::translate('account_submit'));
        html::formEnd();
        echo html::$formStr;
    }
    
    /**
     * displays form for setting new password. 
     */
    public function displayNewPassword () {
        $field_size = array ('size' => '30');
        html::$autoLoadTrigger = 'submit';
        html::init($_POST);
        html::formStart('account_set_new_password');
        html::legend(lang::translate('account_request_legend_set_new_password'));
        html::label('password1', lang::translate('account_form_password'));
        html::password('password1',  null, $field_size);
        html::label('password2', lang::translate('account_form_retype_password'));
        html::password('password2',  null, $field_size);
        //html::label('captcha', captcha::createCaptcha());
        //html::text('captcha', null, array ('size' => 1, 'maxlength'=> 2));
        html::label('submit', '');
        html::submit('submit', lang::translate('account_submit'));
        html::formEnd();
        echo html::$formStr;
    }
    
    /**
     * method for displaying the request password form
     * in a single call. 
     */
    public static function disaplyRequestPassword () {
        template::setTitle(lang::translate('account_request_password_title'));

        simple_prg();
        $request = new requestpw();
        if (isset($_POST['submit'])){
            $request->sanitize();
            $request->validate();

            if (empty($request->errors)){
                $mail_sent = $request->requestPassword($_POST['email']);
                if ($mail_sent){
                    session::setActionMessage(
                        lang::translate('account_request_login_info_sent_to')
                    );
                    $location = get_module_ini('account_default_url');
                    $header = "Location: $location";
                    header($header);
                }
            } else {
                view_form_errors($request->errors);
            }
        }

        $request->displayRequest();
    }
}