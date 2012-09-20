<?php

/**
 * File containg method for creating an account using an email
 * @package account
 */

include_once "coslib/captcha.php";
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
        
        if (!cosValidate::validateEmailAndDomain($_POST['email'])) {
            $this->errors['email'] = lang::translate('account_error_invalid_email');
        }
        
        // if we use two fields for password
        $this->validatePasswords();
        
        if (!captcha::checkCaptcha(trim($_POST['captcha']))){
            $this->errors['captcha'] = lang::translate('account_error_incorrect_captcha');
        }
    }
    
    /**
     * validate passwords 
     */
    function validatePasswords () {

        if (function_exists('common_account_validate_passwords')) {
            // TODO: Allow common function to check 
        }
        
        if (function_exists('mb_strlen')) {
            if (mb_strlen($_POST['password'], 'UTF-8') < 7){
                $this->errors['password'] = lang::translate('account_error_password_length');
            }
        } else {
            if (strlen($_POST['password']) < 7){
                $this->errors['password'] = lang::translate('account_error_password_length');
            }
        }
        if ($_POST['password'] != $_POST['password2']){
            $this->errors['password'] = lang::translate('account_error_password_mismatch');
        }        
    }
    
    /**
     * validate emails from submission $_POST['email'] against $_POST['email2'] 
     */
    function validateEmails () {

                
        if (!cosValidate::validateEmailAndDomain($_POST['email'])) {
            $this->errors['email'] = lang::translate('account_error_invalid_email');
        }
        
        if ($this->emailExist($_POST['email'])){
            $this->errors['email'] = lang::translate('account_error_email_exists');
            return false;
        }
        
        
        if ($_POST['email'] != $_POST['email2']) {
            $this->errors['email'] = lang::translate('account_error_email_does_not_match');
            return false;
        }
    }
    
    /**
     * inserts a user user into database
     * @param string $email
     * @param string $password
     * @return int $last_insert_id
     */
    public function createDbUser ($email, $password, $md5_key) {

        $values = 
            array('username'=> $email,
                  'password' => md5($password),
                  'email' => $email,
                  'md5_key' => $md5_key,
                  'type' => 'email');
        
        $db = new db();
        $db->insert('account', $values);
        $last_insert_id = db::$dbh->lastInsertId();        
        return $last_insert_id;
    }

    /**
     * method for creating a user
     *
     * @return int $res last insert id on success or 0 on failure
     */
    public function createUser (){

        // enter decoded values
        $_POST = html::specialDecode($_POST);
        db::$dbh->beginTransaction();        
        $md5_key = md5(uniqid(rand(), true));
        $last_insert_id = $this->createDbUser(
                $_POST['email'], 
                $_POST['password'], 
                $md5_key
        );

        if ($this->sendVerifyMail($_POST['email'], $last_insert_id, $md5_key)){
            db::$dbh->commit();
            return $last_insert_id;
        } else {
            db::$dbh->rollBack();
            return 0;
        }
    }
    
    /**
     * send a verify email
     * @param type $email
     * @param type $user_id
     * @param type $md5
     * @return boolean $res 
     */
    public function sendVerifyMail ($email, $user_id, $md5) {
        
        $subject = lang::translate('account_signup_subject');
        
        $scheme = config::getHttpScheme();
        $vars['site_name'] = "$scheme://$_SERVER[HTTP_HOST]";
        $subject.= " " . $vars['site_name'];
        $vars['verify_key'] = "$vars[site_name]/account/create/verify/$user_id/$md5";

        // compose message from language
        $lang = config::getMainIni('language');
        $message_template = _COS_PATH . "/modules/account/lang/$lang/signup_message.inc";
        $message = get_include_contents($message_template, $vars);
        
        $from = config::$vars['coscms_main']['site_email'];
        return mail_utf8($email, $subject, $message, $from);
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
        
        $row = $db->selectOne(
                'account', 
                null, 
                array ('email' => $email, 'type' => 'email')
            );

        if (empty($row)) return false;
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
        //$db->begin();
        $search = array ('id' => $account_id,);
        $row = $db->selectOne('account', null, $search);

        if (!empty($row) && $row['verified'] == 1){
            return 2;
        }

        $search = array ('id' => $account_id, 'md5_key' => $md5_key);
        $row = $db->selectOne('account', null, $search);
        
        if (empty($row)){
            $this->errors[] = lang::translate('account_wrong_validation_combination');
            return 0;
        }     
        
        $res = $this->verifyUpdateDb ($row['id']);
        if ($res) {

            $args = array (
                'action' => 'account_create',
                'user_id' => $row['id'],
            );

            event::getTriggerEvent(
                config::getModuleIni('account_events'), 
            $args);
            
            $redirect = config::getModuleIni('account_verify_login'); 
            if ($redirect) {
                $row = user::getAccount($row['id']);
                $a = new account();
                $a->setSesisonAndCookie($row);
                http::locationHeader($redirect);
            }
        
        }
        return $res;
    }
    
    /**
     * updates db on verify account
     * @param int $id user_id
     * @return boolean $res true on success and false on failure 
     */
    public function verifyUpdateDb ($id) {
        $db = new db();
        $md5_key = md5(uniqid(rand(), true));
        $values = array('verified' => 1, 'md5_key' => $md5_key);
        $res = $db->update('account', $values, $id);
        
        $args = array (
            'action' => 'account_verify',
            'user_id' => $id,
        );
                
        event::getTriggerEvent(
            config::getModuleIni('account_events'), 
        $args);
        
        return $res;
    }
}
