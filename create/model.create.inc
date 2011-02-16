<?php

/**
 * model file for account/create
 *
 * @package    account
 */

/**
 * class for creating user accounts
 *
 * @package    account
 */
class accountCreate extends db {

    /**
     *
     * @var array array for holding errors
     */
    public $errors = array();

    /**
     * constructor
     * checks input data
     */
    function __construct($validate = true){
        if ($validate){
            $this->validate();
        }
    }

    /**
     * method for validating creation of account
     *
     * @return void
     */
    function validate(){

        if (!isset($_POST['submit'])){
            $_POST['username'] = '';
            $_POST['password'] = '';
            $_POST['password2'] = '';
            $_POST['email'] = '';
            $_POST['captcha'] = '';
            return null;
        }

        $pattern = "/^([a-zA-Z0-9._])+$/";
        if (!preg_match($pattern, $_POST['username'])){
            $this->errors['username'] = 
            lang::translate('Username can only contain alphabetic chars and number and . and _');
        }

        if (strlen($_POST['username']) < 5){
            $this->errors['username'] = 
            lang::translate('Username should be at least 5 chars long');
        }
        if ($this->usernameExist($_POST['username'])){
            $this->errors['username'] = lang::translate('Username exists. Pick another');
        }
        if ($this->emailExist($_POST['email'])){
            $this->errors['email'] = lang::translate('Email already exists.');
        }
        if (strlen($_POST['password']) < 5){
            $this->errors['password'] = lang::translate('Password should be at least 5 chars long');
        }
        if ($_POST['password'] != $_POST['password2']){
            $this->errors['password'] = lang::translate('Passwords does not match');
        }
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
            $this->errors['email'] = lang::translate('Not a valid email');
        }
        if (!captcha::checkCaptcha(trim($_POST['captcha']))){
            $this->errors['captcha'] = lang::translate("Incorrect answer to captcha test");
        }
    }

    /**
     * method for creating a user
     *
     * @global array    register::$vars['coscms_main'] containing globals mail settings
     * @return int      1 success or 0 on failure
     */
    public function createUser (){
        //global register::$vars['coscms_main'];
        self::$dbh->beginTransaction();
        $md5_key = md5(uniqid(rand(), true));
        try {
            $values = 
                array('username'=> $_POST['username'],
                      'password' => md5($_POST['password']),
                      'email' => $_POST['email'],
                      'md5_key' => $md5_key
                  
                );
             $res = $this->insert('account', $values);
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }

        $message = lang::translate('signup');
        $subject = lang::translate('signup_subject');
        $message = str_replace ('!username!', $_POST['username'], $message);
        $site_name = 'http://' . $_SERVER['HTTP_HOST'];
        $subject = str_replace ('!site_name!', $site_name, $subject);
        $message = str_replace ('!site_name!', $site_name, $message);
        $verify_key = $site_name . '/account/create/verify/1/' . $_POST['username'] . '/' . $md5_key;
        $message = str_replace ('!verify_key!', $verify_key, $message);
        $from = register::$vars['coscms_main']['site_email'];
        if (mail_utf8($_POST['email'], $subject, $message, $from)){
            self::$dbh->commit();
            return 1;
        } else {
            self::$dbh->rollBack();
            return 0;
        }
    }

/**
     * method for creating a user
     *
     * @global array    register::$vars['coscms_main'] containing globals mail settings
     * @return int      1 success or 0 on failure
     */
    public function createSystemUser ($values){
       // global register::$vars['coscms_main'];
        try {           
             $res = $this->insert('account', $values);
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
        return $res;
    }

    /**
     * method for checking if user exists
     *
     * @param   string      username
     * @return  mixed       row with username or false
     */
    public function usernameExist($username){
        try {
            $row = $this->selectOne('account', 'username', $username);
            return $row;
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
        return false;
    }
    /**
     * method for checking if a email exists in `account` table
     *
     * @param   string  the email to be checked for existens
     * @return  array|0 user row or false
     */
    public function emailExist($email){
        try {
            $row = $this->selectOne('account', 'email', $email);
            return $row;
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
        return false;
    }

    /**
     * method for verifing an account
     *
     * @return int 0 (on failure) 2 (user is verified) (res from update 1)
     */
    public function verifyAccount(){
        $uri = uri::getInstance();
        $username = $uri->fragment(4);
        $md5_key = $uri->fragment(5);
        $search = array ('username' => $username, 'md5_key' => $md5_key);
        $row = $this->selectOne('account', null, $search);
        if (empty($row)){
            $this->errors[] = lang::translate
                ('No such combination between user and validation key');
            return 0;
        }

        if ($row['verified'] == 1){
            return 2;
        }
        $md5_key = md5(uniqid(rand(), true));
        $values = array('verified' => 1, 'md5_key' => $md5_key);
        $res = $this->update('account', $values, $row['id']);
        return $res;
    }


}