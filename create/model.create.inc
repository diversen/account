<?php

/**
 * File containg method for creating an account using an email
 * @package account
 */

/**
 * class for creating an account using an email. 
 * @package account
 */
class accountCreate {

    /**
     *
     * @var array $errors for holding errors
     */
    public $errors = array();

    /**
     * constructor
     * checks input data
     */
    function __construct($validate = true){
        if ($validate){
            if (isset($_POST['submit'])) {
                $this->validate();
            }
        }
    }

    /**
     * method for validating creation of account
     * sets $errors if any error
     */
    function validate(){

        if ($this->emailExist($_POST['email'])){
            $this->errors['email'] = lang::translate('account_error_email_exists');
        }
        if (strlen($_POST['password']) < 7){
            $this->errors['password'] = lang::translate('account_error_password_length');
        }
        if ($_POST['password'] != $_POST['password2']){
            $this->errors['password'] = lang::translate('account_error_password_mismatch');
        }
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
            $this->errors['email'] = lang::translate('account_error_invalid_email');
        }
        if (!captcha::checkCaptcha(trim($_POST['captcha']))){
            $this->errors['captcha'] = lang::translate('account_error_incorrect_captcha');
        }
    }

    /**
     * method for creating a user
     *
     * @return int $res 1 on success or 0 on failure
     */
    public function createUser (){
       
        $md5_key = md5(uniqid(rand(), true));

        $values = 
            array('username'=> $_POST['email'],
                  'password' => md5($_POST['password']),
                  'email' => $_POST['email'],
                  'md5_key' => $md5_key);

        // transaction begin. Insert new user
        db::$dbh->beginTransaction();
        $db = new db();
        $db->insert('account', $values);
        $last_insert_id = db::$dbh->lastInsertId();

        // generate email
        $subject = lang::translate('account_signup_subject');
        $vars['site_name'] = 'http://' . $_SERVER['HTTP_HOST'];
        $subject.= " " . $vars['site_name'];
        $vars['verify_key'] = "$vars[site_name]/account/create/verify/$last_insert_id/$md5_key";

        // compose message from language
        $lang = config::getMainIni('language');
        $message_template = _COS_PATH . "/modules/account/lang/$lang/signup_message.inc";
        $message = get_include_contents($message_template, $vars);
        
        $from = config::$vars['coscms_main']['site_email'];
        if (mail_utf8($_POST['email'], $subject, $message, $from)){
            db::$dbh->commit();
            return 1;
        } else {
            db::$dbh->rollBack();
            return 0;
        }
    }

    /**
     * method for creating a user
     * @param array $values the values of the new user. 
     * @return int  $res   true on success and false on failure
     */
    public function createSystemUser ($values){
        $db = new db();
        $res = $db->insert('account', $values);
        return $res;
    }

    /**
     * method for checking if a email exists in `account` table
     *
     * @param   string  $email the email to be checked for existens
     * @return  array|0  $row user row or 0
     */
    public function emailExist($email){
        $db = new db();
        $row = $db->selectOne('account', 'email', $email);
        if (empty($row)) return 0;
        return $row;
    }

    /**
     * method for verifing an account
     *
     * @return int $res 0 (on failure) 2 (user is verified) (res from update 1)
     */
    public function verifyAccount(){
        $uri = uri::getInstance();
        $account_id = $uri->fragment(3);
        $md5_key = $uri->fragment(4);
        
        $db = new db();
        $db->begin();
        $search = array ('id' => $account_id,);
        $row = $db->selectOne('account', null, $search);

        //print_r($row);
        if (!empty($row) && $row['verified'] == 1){
            //echo "returning";
            return 2;
        }

        $search = array ('id' => $account_id, 'md5_key' => $md5_key);
        $row = $db->selectOne('account', null, $search);
        
        if (empty($row)){
            $this->errors[] = lang::translate('account_wrong_validation_combination');
            return 0;
        }     
                
        $md5_key = md5(uniqid(rand(), true));
        $values = array('verified' => 1, 'md5_key' => $md5_key);
        $res = $db->update('account', $values, $row['id']);
       
        $args = array (
            'action' => 'account_create',
            'user_id' => $row['id'],
        );
                
        event::getTriggerEvent(
            config::getModuleIni('account_events'), 
        $args);
        
        $db->commit();
        return $res;
    }
}