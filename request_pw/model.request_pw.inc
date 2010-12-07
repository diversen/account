<?php

/**
 * File for holding class where user can request a new password
 *
 * @package    account
 */

/**
 * model class for requesting a password
 *
 * @package    account
 */
class request extends db {

    /**
     *
     * @var array holding errors
     */
    public $errors;

    /**
     * constructer will validate user input
     */
    function __construct(){
        $this->validate();
    }

    /**
     * method for validating user input
     */
    function validate(){
        if (!isset($_POST['submit'])){
            $_POST['captcha'] = '';
            $_POST['email'] = '';
        }
    }

    /**
     * method for requesting password
     *
     * @return int  1 on succes and 0 on failure
      */
    public function requestPassword (){
        //global register::$vars['coscms_main'];

        if (!captcha::checkCaptcha(trim($_POST['captcha']))){
            $this->errors['captcha'] = "Incorrect answer to captcha test";
        }

        $row = $this->selectOne('account', 'email', $_POST['email']);
        if (empty($row)){
            $this->errors['email'] = lang::translate('No such email');
            return 0;
        }
        $md5_key = md5(uniqid(rand(), true));
        $values = array('md5_key' => $md5_key);
        $this->update('account', $values, $row['id']);
        $message = lang::translate('request_pw');
        $subject = lang::translate('request_pw_subject');

        $message = str_replace ('!username!', $row['username'], $message);
        $site_name = 'http://' . $_SERVER['HTTP_HOST'];
        $subject = str_replace ('!site_name!', $site_name, $subject);
        $message = str_replace ('!site_name!', $site_name, $message);
        $verify_key = $site_name . '/account/request_pw/verify/1/' . $row['username'] . '/' . $md5_key;
        $message = str_replace ('!verify_key!', $verify_key, $message);
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
        $username = $uri->fragment(4);
        $md5_key = $uri->fragment(5);
        $search = array ('username' => $username, 'md5_key' => $md5_key);
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
        if (strlen($_POST['password']) < 5){
            $this->errors['password'] = lang::translate('Password should be at least 5 chars long');
        }
        if ($_POST['password'] != $_POST['password2']){
            $this->errors['password'] = lang::translate('Passwords does not match');
        }
    }

    /**
     * method for verifying account from link sent in email
     *
     * @return boolean  true on success or false on failure
     */
    public function verifyAccount(){
        $uri = uri::getInstance();
        $username = $uri->fragment(4);
        $md5_key = $uri->fragment(5);
        $search = array ('username' => $username, 'md5_key' => $md5_key);
        $row = $this->selectOne('account', null, $search);
        if (empty($row)){
            $this->errors[] = lang::translate(
                'No such combination between user and validation key');
            return false;
        } else {
            return true;
        }
    }
}