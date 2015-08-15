<?php

namespace modules\account\requestpw;
/**
 * File containing class for srequesting and etting a new user password
 * 
 */

use diversen\captcha;
use diversen\db;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\mailer;
use diversen\random;
use diversen\session;
use diversen\strings\mb;
use diversen\template;
use diversen\valid;
use diversen\view;

use modules\account\module as account;


view::includeOverrideFunctions('account', 'requestpw/views.php');


/**
 * class requestpw 
 */
class module extends account {

    /**
     * options
     * @var array $options 
     */
    public $options = array();

    /**
     * constructor. set options
     * @param array $options
     */
    function __construct($options = array()) {
        $this->options = $options;
    }

    /**
     * method for sanitizing input. 
     */
    public function sanitize() {
        $_POST = html::specialEncode($_POST);
    }

    /**
     * method for validating input
     */
    public function validate() {
        if (!captcha::checkCaptcha(trim($_POST['captcha']))) {
            $this->errors['captcha'] = lang::translate('Wrong answer to captcha test');
        }

        $row = $this->getUserFromEmail($_POST['email']);
        if (empty($row)) {
            $this->errors['email'] = lang::translate('Email does not exists in our system');
            return;
        }

        if ($row['type'] != 'email') {
            $this->errors['type'] = lang::translate('Email is connected to an account of this type: <span class="notranslate">{ACCOUNT_TYPE}</span>', array('ACCOUNT_TYPE' => $account['type']));
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
    public $mailViews = array(
        'txt' => 'mails/request_password',
        'html' => 'mails/request_password_html');

    /**
     * 
     * @param string $module e.g. 'account_ext'
     * @param array  $views e.g. array (
     *                              'html' => 'mails/signup_message_html',
     *                              'txt' => 'mails/signup_message',
     */
    public function setRequestMail($module, $views = array()) {
        $this->mailModule = $module;

        if (isset($views['txt'])) {
            $this->mailViews['txt'] = $views['txt'];
        }

        if (isset($views['html'])) {
            $this->mailViews['html'] = $views['html'];
        }
    }

    /**
     * method for requesting password
     *
     * @return int  $res 1 on succes and 0 on failure
     */
    public function requestPassword($email = null, $options = array()) {

        $email = mb::tolower($email);
        $md5_key = random::md5();

        $db = new db();
        $search = array('email' => $email, 'type' => 'email');
        $row = $db->selectOne('account', 'email', $search);

        $values = array('md5_key' => $md5_key);
        $db->update('account', $values, $row['id']);

        $vars['user_id'] = $row['id'];
        $vars['site_name'] = 'http://' . $_SERVER['HTTP_HOST'];
        $subject = lang::translate('Create new password for site') . " " . $vars['site_name'];

        // allow class to be used in other setups
        if (isset($this->options['verify_path'])) {
            $path = $this->options['verify_path'];
        } else {
            $path = "/account/requestpw/verify";
        }

        $vars['verify_key'] = "$vars[site_name]$path?id=$row[id]&md5=$md5_key";
        if (isset($this->options['verify_path_prepend'])) {
            $vars['verify_key'].=$this->options['verify_path_prepend'];
        }

        $message = $this->getRequestMail($vars);

        $res = mailer::multipart($row['email'], $subject, $message);
        return $res;
    }

    /**
     * gets request password mail message
     * @param  array $vars e.g. array (
     *                      'site_name' => 'http://example.com', 
     *                      'user_id' => 123, 
     *                      'verify_key' => 'http link to verify')
     * @return array $message e.g. array (
     *                      'txt' => 'text welcome etc', 
     *                      'html' => 'html message');
     */
    public function getRequestMail($vars) {

        // option for multi part message
        $message = array();

        // if a mail view == null then it is not added
        if (isset($this->mailViews['txt']) && $this->mailViews['txt'] != null) {
            $message['txt'] = view::get($this->mailModule, $this->mailViews['txt'], $vars);
        }
        if (isset($this->mailViews['html']) && $this->mailViews['html'] != null) {
            $message['html'] = view::get($this->mailModule, $this->mailViews['html'], $vars);
        }
        return $message;
    }

    /**
     * method for setting a new password
     *
     * @return boolean $res true on success and false on failure
     */
    public function setNewPassword() {
        $id = $_GET['id'];
        $md5_key = $_GET['md5'];
        $password = html::specialDecode($_POST['password1']);
        return $this->setPasswordDb($id, $md5_key, $password);
    }

    /**
     * sets new password for user from user_id, md5_key
     * @param int $id
     * @param string $md5_key
     * @param string $password
     * @return boolean $res
     */
    public function setPasswordDb($id, $md5_key, $password) {
        $search = array('id' => $id, 'md5_key' => $md5_key);

        $db = new db();
        $row = $db->selectOne('account', null, $search);
        $new_md5 = random::md5();

        $values = array('verified' => 1, 'password' => md5($password), 'md5_key' => $new_md5);
        $res = $db->update('account', $values, $row['id']);
        return $res;
    }

    /**
     * set new md5 key
     * @param int $user_id
     */
    public function setNewMd5($user_id) {
        $db = new db();
        $md5_key = random::md5();

        $values = array('md5_key' => $md5_key);
        return $db->update('account', $values, $user_id);
    }

    /**
     * method for validating password from POST
     * sets errors if any
     */
    public function validatePasswordFromPost() {
        $this->validatePasswords($_POST['password1'], $_POST['password2']);
    }

    /**
     * method for validating passwords. sets $this->errors
     * @param string $password1
     * @param string $password2
     */
    public function validatePasswords($password1, $password2) {
        $len = valid::passwordLength($password1, 7);
        if (!$len) {
            $this->errors['password_length'] = lang::translate('Password has to be at least 7 chars');
        }

        $match = valid::passwordMatch($password1, $password2);
        if (!$match) {
            $this->errors['password_match'] = lang::translate('Passwords does not match');
        }
    }

    /**
     * method for verifying account from link sent in email
     *
     * @return boolean  true on success or false on failure
     */
    public function verifyAccount() {

        $row = $this->getAccountFromMd5();

        if (empty($row)) {
            $this->errors[] = lang::translate('No such combination between token and email. You can only use the token one time');
            return false;
        } else {
            return true;
        }
    }

    /**
     * /account/requestpw/verify
     */
    public function verifyAction() {
        template::setTitle(lang::translate('Create new password'));

        $request = new self;
        $res = $request->verifyAccount();
        if ($res) {
            $request->sanitize();
            if (isset($_POST['submit'])) {
                $request->validatePasswordFromPost();
                if (empty($request->errors)) {
                    if ($request->setNewPassword()) {
                        session::setActionMessage(
                                lang::translate('New password has been saved')
                        );
                        http::locationHeader('/account/login/index');
                    }
                } else {
                    html::errors($request->errors);
                }
            }
            views::formVerify();
        } else {
            html::errors($request->errors);
        }
    }

    /**
     * gets account from md5 - uses $GET['md5'] and $_GET['id']
     * @return array $account
     */
    public function getAccountFromMd5() {
        $id = $_GET['id'];
        $md5_key = $_GET['md5'];
        $search = array('id' => $id, 'md5_key' => $md5_key);

        $db = new db();
        $row = $db->selectOne('account', null, $search);
        return $row;
    }

    /**
     * method for displaying the request password form
     * in a single call. 
     */
    public function indexAction() {
        template::setTitle(lang::translate('Request new password'));

        http::prg();
        $request = new self();
        if (isset($_POST['submit'])) {
            $request->sanitize();
            $request->validate();

            if (empty($request->errors)) {
                $mail_sent = $request->requestPassword($_POST['email']);
                if ($mail_sent) {
                    session::setActionMessage(
                            lang::translate('Visit your mailbox and follow instructions in order to get a new password'), true
                    );

                    http::locationHeader('/account/login/index');
                } else {
                    echo html::getError(
                            lang::translate('We could not not send you an email. Please try again later'));
                    return;
                }
            } else {
                echo html::getErrors($request->errors);
            }
        }

        views::formSend();
    }
}