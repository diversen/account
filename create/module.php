<?php

/**
 * File containg method for creating an account using an email
 * @package account
 */
moduleloader::includeModule('account');
view::includeOverrideFunctions('account', 'login/views.php');
/**
 * class for creating an account using an email. 
 * @package account
 */
class account_create extends account {

    /**
     * constructor
     * checks input data
     */
    function __construct($options = array ()){
        $this->options = $options;
    }

    /**
     * method for validating creation of account
     * sets $errors if any error
     */
    function validate(){

        if (isset($_POST['submit'])) {
            if ($this->emailExist($_POST['email'])){
                $this->errors['email'] = lang::translate('Email already exists');
            }

            if (!cosValidate::validateEmailAndDomain($_POST['email'])) {
                $this->errors['email'] = lang::translate('That is not a valid email');
            }

            // if we use two fields for password
            $this->validatePasswords();

            if (!captcha::checkCaptcha(trim($_POST['captcha']))){
                $this->errors['captcha'] = lang::translate('Incorrect answer to captcha test');
            }
        }
    }
      
    /**
     * validate passwords 
     */
    public function validatePasswords () {
        
        $l = cosValidate::passwordLength($_POST['password'], 7);
        if (!$l) {
            $this->errors['password'] = lang::translate('Password needs to be 7 chars');
        }
        
        $m = cosValidate::passwordMatch($_POST['password'], $_POST['password2']);
        if (!$m){
            $this->errors['password'] = lang::translate('Passwords does not match');
        }        
    }
    
    /**
     * set this to true if you want to ignore if a mail exist when signing up
     * sets $this->options['ignore_email_exists']
     * @param  boolean $bool
     */
    public function ignoreEmailExists ($bool) {
        if ($bool) {
            $this->options['ignore_email_exists'] = true;
        }
    }
    
    /**
     * validate emails from submission $_POST['email'] against $_POST['email2']
     */
    public function validateEmails () {

                
        if (!cosValidate::validateEmailAndDomain($_POST['email'])) {
            $this->errors['email'] = lang::translate('That is not a valid email');
        }
        
        if (!isset($this->options['ignore_email_exists'])) {
            if ($this->emailExist($_POST['email'])){
                $this->errors['email'] = lang::translate('Email already exists');
                return false;
            }
        }
    }
    
    /**
     * check for identical email from $_POST['email'] and $_POST['email2']
     * @return boolean $res 
     */
    public function validateIdenticalEmail () {
                
        if ($_POST['email'] != $_POST['email2']) {
            $this->errors['email'] = lang::translate('Emails does not match');
            return false;
        }
        return true;
    }
    
    /**
     * inserts a user user into database.
     * runs the event action account_create with the created user_id
     * as param
     * 
     * @param string $email
     * @param string $password
     * @param string $md5_key
     * @return int $last_insert_id
     */
    public function createDbUser ($email, $password, $md5_key) {

        $values = 
            array('username'=> strings_mb::tolower($email),
                  'password' => md5($password),
                  'email' => strings_mb::tolower($email),
                  'md5_key' => $md5_key,
                  'type' => 'email');
        
        $db = new db();
        $db->insert('account', $values);
        $last_insert_id = db::$dbh->lastInsertId();
        
        // create events
        $args = array (
            'action' => 'account_create',
            'user_id' => $last_insert_id,
        );
                
        event::getTriggerEvent(
            config::getModuleIni('account_events'), 
        $args);
        
        return $last_insert_id;
    }

    /**
     * method for creating an email user from POST
     * we need a $_POST['email'] and a $_POST['password']
     * Calls $this->sendVerify email
     * @return int $res last insert id on success or 0 on failure
     */
    public function createUser (){
                
        $_POST['email'] = strings_mb::tolower($_POST['email']);
        
        // enter decoded values
        $_POST = html::specialDecode($_POST);
        db::$dbh->beginTransaction();        
        $md5_key = random::md5();
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

        $subject = lang::translate('Account created');
        
        $scheme = config::getHttpScheme();
        $vars['site_name'] = "$scheme://$_SERVER[HTTP_HOST]";
        $subject.= " " . $vars['site_name'];
        $vars['verify_key'] = "$vars[site_name]/account/create/verify/$user_id/$md5";
        $vars['user_id'] = $user_id;

        // option for multi part message
        $message = array ();        
        
        $txt_message = view::get('account', "mails/signup_message", $vars);
        $html_message = view::get('account', "mails/signup_message_html", $vars);
        
        $message['txt'] = $txt_message;
        $message['html'] = $html_message;
        
        $from = config::$vars['coscms_main']['site_email'];
        return cosMail::multipart($email, $subject, $message, $from);
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
     * method for verifing an account
     *
     * @return int $res 0 (on failure) 2 (user is verified) (res from update 1)
     */
    public function verifyAccount(){
        $uri = uri::getInstance();
        $account_id = $uri->fragment(3);
        $md5_key = $uri->fragment(4);
        
        $db = new db();
        $search = array ('id' => $account_id,);
        $row = $db->selectOne('account', null, $search);

        if (!empty($row) && $row['verified'] == 1){
            return 2;
        }

        $search = array ('id' => $account_id, 'md5_key' => $md5_key);
        $row = $db->selectOne('account', null, $search);
        
        if (empty($row)){
            $this->errors[] = lang::translate('Wrong validation key on account creation');
            return 0;
        }     
        
        $res = $this->verifyUpdateDb ($row['id']);
        if ($res) {            
            $redirect = config::getModuleIni('account_verify_login'); 
            if ($redirect) {
                $row = user::getAccount($row['id']);
                $a = new account();
                $a->setPersistentCookie(true);
                $a->setSessionAndCookie($row);
                http::locationHeader($redirect);
            }
        }
        return $res;
    }
    
    /**
     * updates db on verify account
     * triggers events with action account_verify and user_id as params
     * @param int $id user_id
     * @return boolean $res true on success and false on failure 
     */
    public function verifyUpdateDb ($id) {
        $db = new db();
        $md5_key = random::md5();
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
