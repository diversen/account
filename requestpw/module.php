<?php

/**
 * File containing class for srequesting and etting a new user password
 * 
 */
view::includeOverrideFunctions('account', 'requestpw/views.phtml');
moduleloader::includeModule('account');

/**
 * class requestpw 
 */
class account_requestpw extends account {


    /**
     * options
     * @var array $options 
     */
    public $options = array ();
    
    /**
     * constructor. set options
     * @param array $options
     */
    function __construct ($options = array ()) {
        $this->options = $options;
        
    }

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
        $search = array ('email' => $_POST['email'], 'type' => 'email');
        $row = $db->selectOne('account', 'email', $search);
        if (empty($row)){
            $this->errors['email'] = lang::translate('account_error_no_such_email');
        }
    }

    /**
     * method for requesting password
     *
     * @return int  $res 1 on succes and 0 on failure
      */
    public function requestPassword ($email = null, $options = array ()){   

        $email = strings_mb::tolower($email);
        $md5_key = random::md5();
        
        $db = new db();
        $search = array ('email' => $email, 'type' => 'email');
        $row = $db->selectOne('account', 'email', $search);
        
        $values = array('md5_key' => $md5_key);
        $db->update('account', $values, $row['id']);
        
        $vars['user_id'] = $row['id'];
        $vars['site_name'] = 'http://' . $_SERVER['HTTP_HOST'];
        $subject = lang::translate('account_request_pw_subject_for_site') . " " . $vars['site_name'];

        // allow class to be used in other setups
        if (isset($this->options['verify_path'])) {
            $path = $this->options['verify_path'];
        } else {
            $path = "/account/requestpw/verify";
        }
        
        $vars['verify_key'] = "$vars[site_name]$path?id=$row[id]&md5=$md5_key";
        if (isset($this->options['verify_path_prepend']))  {
            $vars['verify_key'].=$this->options['verify_path_prepend'];
        }
        
        $lang = config::getMainIni('language');        
        
        $message['txt'] = view::get('account', "lang/$lang/request_password", $vars);
        $message['html'] = view::get('account', "lang/$lang/request_password_html", $vars);

        $res = cosMail::multipart($row['email'], $subject, $message);
        return $res;
    }

    /**
     * method for setting a new password
     *
     * @return boolean $res true on success and false on failure
     */
    public function setNewPassword(){
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
    public function setPasswordDb ($id, $md5_key, $password) {
        $search = array ('id' => $id, 'md5_key' => $md5_key);
        
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
    public function setNewMd5 ($user_id) { 
        $db = new db();
        $md5_key = random::md5();

        $values = array('md5_key' => $md5_key);
        return $db->update('account', $values, $user_id);
        
    }

    /**
     * method for validating password from POST
     * sets errors if any
     */
    public function validatePasswordFromPost(){
        $this->validatePasswords($_POST['password1'], $_POST['password2']);
    }
    
    /**
     * method for validating passwords. sets $this->errors
     * @param string $password1
     * @param string $password2
     */
    public function validatePasswords ($password1, $password2) {
        $len = cosValidate::passwordLength($password1, 7);
        if (!$len) {
            $this->errors['password_length'] = lang::translate('account_password_length_error');
        }
        
        $match = cosValidate::passwordMatch($password1, $password2);
        if (!$match) {
            $this->errors['password_match'] = lang::translate('account_password_no_match');
        }
    }
    

    /**
     * method for verifying account from link sent in email
     *
     * @return boolean  true on success or false on failure
     */
    public function verifyAccount(){
        
        $row = $this->getAccountFromMd5();
        
        if (empty($row)){
            $this->errors[] = lang::translate('account_request_no_combination_md5_user');
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * gets account from md5 - uses $GET['md5'] and $_GET['id']
     * @return array $account
     */
    public function getAccountFromMd5 () {
        $id = $_GET['id']; 
        $md5_key = $_GET['md5'];
        $search = array ('id' => $id, 'md5_key' => $md5_key);

        $db = new db();
        $row = $db->selectOne('account', null, $search);
        return $row;
    }

    
    /**
     * method for displaying the request password form
     * in a single call. 
     */
    public static function displayRequestPassword () {
        template::setTitle(lang::translate('account_request_password_title'));

        http::prg();
        $request = new account_requestpw();
        if (isset($_POST['submit'])){
            $request->sanitize();
            $request->validate();

            if (empty($request->errors)){
                $mail_sent = $request->requestPassword($_POST['email']);
                if ($mail_sent){
                    session::setActionMessage(
                        lang::translate('account_request_login_info_sent_to'), true
                    );
                    $location = config::getModuleIni('account_default_url');
                    http::locationHeader($location);
                } else {
                    html::errors(array ('Could not send email'));
                    return;
                }
            } else {
                html::errors($request->errors);
            }
        }

        account_requestpw_views::formSend();
    }
}