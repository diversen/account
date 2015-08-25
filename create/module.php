<?php

namespace modules\account\create;
/**
 * File containg method for creating an account using an email
 * @package account
 */

use diversen\captcha;
use diversen\conf;
use diversen\html;
use diversen\db;
use diversen\http;
use diversen\lang;
use diversen\mailer;
use diversen\random;
use diversen\strings\mb;
use diversen\template;
use diversen\uri;
use diversen\user;
use diversen\valid;
use diversen\view;

use modules\account\module as account;
use modules\account\config;

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
            
            // exists in system
            if ($this->emailExist($_POST['email'])){
                
                $this->errors['email'] = lang::translate('Email already exists');
                $account = $this->getUserFromEmail($_POST['email'], null);
                if ($account['type'] != 'email') {
                    $this->errors['type'] = lang::translate('Email is connected to an account of this type: <span class="notranslate">{ACCOUNT_TYPE}</span>', array ('ACCOUNT_TYPE' => $account['type']));
                }
            }

            // validate email and email domain
            if (!valid::validateEmailAndDomain($_POST['email'])) {
                $this->errors['email'] = lang::translate('That is not a valid email');
            }

            // validate passeword
            $this->validatePasswords();
            
            // captcha
            if (!captcha::checkCaptcha(trim($_POST['captcha']))){
                $this->errors['captcha'] = lang::translate('Incorrect answer to captcha test');
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
              
        if (!valid::validateEmailAndDomain($_POST['email'])) {
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
            array('username'=> mb::tolower($email),
                  'password' => md5($password),
                  'email' => mb::tolower($email),
                  'md5_key' => $md5_key,
                  'type' => 'email');
        
        $db = new db();
        $db->insert('account', $values);
        $last_insert_id = db::$dbh->lastInsertId();
        
        config::onCreateUser($last_insert_id);
        return $last_insert_id;
    }

    /**
     * method for creating an email user from POST
     * we need a $_POST['email'] and a $_POST['password']
     * Calls $this->sendVerify email
     * @return int $res last insert id on success or 0 on failure
     */
    public function createUser (){
                
        $_POST['email'] = mb::tolower($_POST['email']);
        
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
     * var holding mailModule. set this with setSignupMail
     * @var string $mailModule
     */
    public $mailModule = 'account';
    
    /**
     * var holding mailViews. set this with setSignupMail
     * @var type 
     */
    public $mailViews = array (
        'txt' => 'mails/signup_message', 
        'html' => 'mails/signup_message_html');
    
    /**
     * 
     * @param string $module e.g. 'account_ext'
     * @param array  $views e.g. array (
     *                              'html' => 'mails/signup_message_html',
     *                              'txt' => 'mails/signup_message',
     */
    public function setSignupMail ($module, $views = array ()) {
        $this->mailModule = $module;
        
        if (isset($views['txt'])) {
            $this->mailViews['txt'] = $views['txt'];
        }
        
        if (isset($views['html'])) {
            $this->mailViews['html'] = $views['html'];
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
        
        $scheme = conf::getHttpScheme();
        $vars['site_name'] = "$scheme://$_SERVER[HTTP_HOST]";
        $subject.= " " . $vars['site_name'];
        $vars['verify_key'] = "$vars[site_name]/account/create/verify/$user_id/$md5";
        $vars['user_id'] = $user_id;

        $message = $this->getWelcomeMail($vars);
        $from = conf::$vars['coscms_main']['site_email'];
        return mailer::multipart($email, $subject, $message, $from);
    }
    
    /**
     * gets welcome message
     * @param  array $vars e.g. array (
     *                      'site_name' => 'http://example.com', 
     *                      'user_id' => 123, 
     *                      'verify_key' => 'http link to verify')
     * @return array $message e.g. array (
     *                      'txt' => 'text welcome etc', 
     *                      'html' => 'html message');
     */
    public function getWelcomeMail ($vars) {
        
        // option for multi part message
        $message = array (); 

        // if a mail view == null then it is not added
        if (isset($this->mailViews['txt']) && $this->mailViews['txt'] != null) {
            $message['txt'] = view::get($this->mailModule, $this->mailViews['txt'], $vars);
        }
        if (isset($this->mailViews['html'])  && $this->mailViews['html'] != null) {
            $message['html'] = view::get($this->mailModule, $this->mailViews['html'], $vars);
        }
        return $message;
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
