<?php

class request extends db {

    /**
     *
     * @var array $errors
     */
    public $errors;

    /**
     * constructer will validate user input
     */
    function __construct(){
    }

    public function sanitize (){
        $_POST = html::entitiesEncode($_POST);
    }

    /**
     * method for validating user input
     */
    function validate(){
        if (!captcha::checkCaptcha(trim($_POST['captcha']))){
            $this->errors['captcha'] = lang::translate('account_error_incorrect_captcha');
        }

        $row = $this->selectOne('account', 'email', $_POST['email']);
        if (empty($row)){
            $this->errors['email'] = lang::translate('account_error_no_such_email');
            return 0;
        }
    }

    /**
     * method for requesting password
     *
     * @return int  1 on succes and 0 on failure
      */
    public function requestPassword (){   
        $md5_key = md5(uniqid(rand(), true));

        $row = $this->selectOne('account', 'email', $_POST['email']);

        $values = array('md5_key' => $md5_key);
        $this->update('account', $values, $row['id']);
        
        $vars['site_name'] = 'http://' . $_SERVER['HTTP_HOST'];
        $subject = lang::translate('account_request_pw_subject_for_site') . " " . $vars['site_name'];

        $vars['verify_key'] = "$vars[site_name]/account/request_pw/verify/$row[id]/$md5_key";
   
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
     * @return int update id
     */
    public function setNewPassword(){
        $uri = uri::getInstance();
        $id = $uri->fragment(3);
        $md5_key = $uri->fragment(4);
        $search = array ('id' => $id, 'md5_key' => $md5_key);
        $row = $this->selectOne('account', null, $search);
        $md5_key = md5(uniqid(rand(), true));
        $values = array('password' => md5($_POST[password]), 'md5_key' => $md5_key);
        $res = $this->update('account', $values, $row[id]);
        return $res;
    }

    /**
     * method for validating password
     * sets errors if any
     */
    public function validatePassword(){
        if (strlen($_POST['password']) < 7){
            $this->errors['password'] = lang::translate('account_password_length_error');
        }
        if ($_POST['password'] != $_POST['password2']){
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
        $row = $this->selectOne('account', null, $search);
        if (empty($row)){
            $this->errors[] = lang::translate('account_request_no_combination_md5_user');
            return false;
        } else {
            return true;
        }
    }
}