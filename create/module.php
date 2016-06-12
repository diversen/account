<?php

namespace modules\account\create;
/**
 * File containg method for creating an account using an email
 * @package account
 */

use diversen\captcha;
use diversen\conf;
use diversen\db;
use diversen\db\q;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\mailer\markdown;
use diversen\mailsmtp;
use diversen\random;
use diversen\strings\mb;
use diversen\template;
use diversen\uri;
use diversen\user;
use diversen\valid;
use diversen\view;
use modules\account\config;
use modules\account\module as account;

view::includeOverrideFunctions('account', 'login/views.php');
/**
 * class for creating an account using an email. 
 * @package account
 */
class module extends account {

    /**
     * constructor
     * checks input data
     */
    function __construct($options = array ()){
        $this->options = $options;
    }

    /**
     * /account/create/verify action
     */
    public function verifyAction() {

        template::setTitle(lang::translate('Verify Account'));
        $a = new self();
        $a->validate();
        $res = $a->verifyAccount();
        if (!$res) {
            echo html::getErrors($a->errors);
        } else if ($res === 2) {
            views::verify(lang::translate('Account is already verified'));
        } else {
            views::verify(lang::translate('Account has been verified'));
        }
    }

    /**
     * method for validating creation of account
     * sets $errors if any error
     */
    public function validate(){

        if (isset($_POST['submit'])) {
            
            $this->validateEmail($_POST['email']);
            $this->validatePasswords();
            if (!captcha::checkCaptcha()){
                $this->errors['captcha'] = lang::translate('Incorrect answer to captcha test');
            }
        }
    }
    
    /**
     * Validate an email. Check email and check if email already exists
     * Sets errors
     * @param string $email
     */
    public function validateEmail($email) {

        if ($this->emailExistInAccount($email)) {

            $this->errors['email'] = lang::translate('Email already exists');
            $account = $this->getUserFromEmail($email, null);
            if ($account['type'] != 'email') {
                $this->errors['type'] = lang::translate('Email is connected to an account of this type: <span class="notranslate">{ACCOUNT_TYPE}</span>', array('ACCOUNT_TYPE' => $account['type']));
            }
        }

        // validate email and email domain
        if (!valid::validateEmailAndDomain($email)) {
            $this->errors['email'] = lang::translate('That is not a valid email');
        }
        
        $this->validateEmailDomains();
    }
    
    public function validateEmailDomains () {
                
        $allow = conf::getModuleIni('account_domain_allow');
        if ($allow) {
            $domains = explode(',', $allow);
            if (!\diversen\mailer\helpers::isValidDomainEmail($email, $domains)) {
                $this->errors['email'] = lang::translate('Emails from the entered domain is not allowed');
            }
        }
    }

    /**
     * validate passwords 
     */
    public function validatePasswords () {
        
        $l = valid::passwordLength($_POST['password'], 7);
        if (!$l) {
            $this->errors['password'] = lang::translate('Password needs to be 7 chars');
        }
        
        $m = valid::passwordMatch($_POST['password'], $_POST['password2']);
        if (!$m){
            $this->errors['password'] = lang::translate('Passwords does not match');
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
    public function createDbUser ($email, $password, $md5_key, $invited = 0) {
        
        $values = 
            array('username'=> mb::tolower($email),
                  'password' => md5($password),
                  'email' => mb::tolower($email),
                  'md5_key' => $md5_key,
                  'type' => 'email',
                  'invited' => $invited);
        
        $db = new db();
        return $db->insert('account', $values);
        
    }

    /**
     * Method for creating an email user from POST
     * we need a $_POST['email'] and a $_POST['password']
     * Calls $this->sendVerify email
     * @return int $res last insert id on success or 0 on failure
     */
    public function createUser (){
        
        $_POST = html::specialDecode($_POST);
        $_POST['email'] = mb::tolower($_POST['email']);
        
        $md5_key = random::md5();
        
        q::begin();
        $res = $this->createDbUser(
                $_POST['email'], 
                $_POST['password'], 
                $md5_key
        );
        
        if ($res) {
            $last_insert_id = q::lastInsertId();
        } else {
            q::rollback();
            $this->errors[] = lang::translate('We could not create user. Please try again later!');
            return false;
        }
        
        $sent = $this->sendVerifyMail($_POST['email'], $last_insert_id, $md5_key);
        if (!$sent) {
            q::rollback();
            $this->errors[] = lang::translate('We could not send an verification email. Try to create user later.');
            return false;
        } else {
            if (!q::commit()) {
                $this->errors[] = lang::translate('We could not create user. Please try again later!');
                q::rollback();
                return false;
            }
        }
        return true;
    }
        
    /**
     * Send a verify email
     * @param string $email
     * @param int $user_id
     * @param string $md5
     * @return boolean $res 
     */
    public function sendVerifyMail ($email, $user_id, $md5) {

        $subject = lang::translate('Account created');

        $vars['site_name'] = conf::getSchemeWithServerName();
        $subject.= " " . $vars['site_name'];
        $vars['verify_key'] = "$vars[site_name]/account/create/verify/$user_id/$md5";
        $vars['user_id'] = $user_id;

        $txt = view::get('account', 'mails/signup_message', $vars);
        $md = new markdown();
        $html = $md->getEmailHtml($subject, $txt);

        return mailsmtp::mail($email, $subject, $txt, $html);

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
            $redirect = conf::getModuleIni('account_verify_login'); 
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
     * Updates the 'account' table when an account is verified
     * triggers events with user_id as param
     * @param int $id user_id
     * @return boolean $res true on success and false on failure 
     */
    public function verifyUpdateDb ($id) {
        $db = new db();
        $md5_key = random::md5();
        $values = array('verified' => 1, 'md5_key' => $md5_key);
        $res = $db->update('account', $values, $id);
        
        // event after update
        config::onVerification($id);
        
        return $res;
    }
}
