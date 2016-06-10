<?php

namespace modules\account\admin;

use diversen\conf;
use diversen\db;
use diversen\db\q;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\pagination;
use diversen\random;
use diversen\session;
use diversen\template;
use diversen\uri;
use diversen\valid;
use modules\account\admin\views as adminViews;
use modules\account\module as account;


// view::includeOverrideFunctions('account', 'admin/views.php');
/**
 * Class account_admin
 */
class module extends account {


    

     /**
     * Action where admin ('can be changed in account.ini') creates account
     * /account/login/create action
     * @return void
     */
    public function createAction() {

        http::prg();

        $allow = conf::getModuleIni('account_allow_create');
        if (!session::checkAccess($allow)) {
            return;
        }
        

        template::setTitle(lang::translate('Create Account'));
        $l = new \modules\account\create\module();
        if (!empty($_POST['submit'])) {
            $_POST = html::specialEncode($_POST);
            $this->validateInvite();
            if (empty($l->errors)) {
                
                // Set mail view
                $l->setVerifyMailTemplate = 'mails/signup_invite';
                $res = $l->createUser();
                if ($res) {
                    http::locationHeader(
                        '/account/login/index', 
                        lang::translate('Account has been created. Visit your email box and press the verification link.'));
                } else {
                    echo html::getErrors($l->errors);
                }
            } else {
                echo html::getErrors($l->errors);
            }
        }

        echo self::formCreate();
        echo \modules\account\views::getTermsLink();
    }
    
    public function validateInvite () {
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
        }
    }

            /**
     * Create form
     */
    public static function formCreate () {
        $options = array ();
        html::$autoLoadTrigger = 'submit';
        html::init($_POST);
        html::formStart('account_create_form');
        html::legend(lang::translate('Create account'));
        html::label('email', lang::translate('Email'), array('required' => 1));
        html::text('email',  null, $options);
        html::hidden('password', random::sha1());
        html::hidden('password2', random::sha1());
        html::hidden('captcha', random::sha1());
        html::submit('submit', lang::translate('Send'));
        html::formEnd();
        return html::getStr();
    
    }
    
    public function indexAction () {
        $this->listAction();
    }

    /**
     * list action
     * /account/admin/list
     * @return type
     */
    public function listAction() {
        
        $allow = conf::getModuleIni('account_allow_create');
        if (!session::checkAccess($allow)) {
            return;
        }

        $per_page = 50;
        $num_rows = $this->getNumUsers();
        $p = new pagination($num_rows, $per_page);
        
        $users = $this->getUsers($p->from, $per_page);
        template::setTitle(lang::translate('Search for users'));

        echo html::getHeadline(lang::translate('All users'), 'h2');
        adminViews::listUsers($users);
        
        echo $p->getPagerHTML();
    }
    
    public function searchAction () {
        $_GET = html::specialEncode($_GET);
        $this->searchAccount();
       
        if (isset($_GET['submit'])) {
            
            $acc = new account();
            $res = $acc->searchIdOrEmail($_GET['id']);
            
            if (!empty($res)) {
                echo lang::translate('Found the following accounts');
                
                echo html::getHeadline(lang::translate('Search results'), 'h2');
                adminViews::listUsers($res);
                //echo "<hr />\n";
            } else {
                echo lang::translate('I could not find any matching results');
            }
        }
    }

    /**
     * edit action
     * @return /account/admin/edit
     */
    public function editAction() {
        $allow = conf::getModuleIni('account_allow_create');
        if (!session::checkAccess($allow)) {
            return;
        }

        template::setTitle(lang::translate('Edit account'));
        $user = $this->getUser();

        if (isset($_POST['submit'])) {
            if (empty($user['url'])) {
                $this->validate();
            }
            if (empty($this->errors)) {
                if (!empty($user['url'])) {
                    $res = $this->updateUrlUser();
                } else {
                    $res = $this->updateEmailUser();
                }
                if ($res) {
                    session::setActionMessage(
                            lang::translate('Account has been updated')
                    );
                    http::locationHeader('/account/admin/list');
                }
            } else {
                echo html::getErrors($this->errors);
            }
        }

        if (!empty($user['url'])) {
            adminViews::updateUrlUser($user);
        } else {
            adminViews::updateEmailUser($user);
        }
    }

    /**
     * 
     * @var type $errors 

     * 
     */
    //public $errors = array();
    public $uri;
    public $id;

    /**
     *  set uri and if from fragement in uri
     */
    function __construct() {
        $this->uri = uri::getInstance();
        $this->id = (int) $this->uri->fragment(3);
    }


    /**
     * get user id from URL
     * @return  int $user_id
     */
    public static function getUserId() {
        $uri = uri::getInstance();
        return (int) $uri->fragment(3);
    }

    /**
     * Validate and sets $error
     */
    function validate($options = null) {
        if (empty($_POST['submit'])) {
            return;
        }
        if (strlen($_POST['password']) < 7) {
            $this->errors['password'] = lang::translate('Password needs to be 7 chars');
        }
        if (strlen($_POST['password']) == 0) {
            unset($this->errors['password']);
        }
        if ($_POST['password'] != $_POST['password2']) {
            $this->errors['password'] = lang::translate('Passwords does not match');
        }
    }

    /**
     * method for getting a user from url fragemtn
     *
     * @return array $row
     */
    public function getUser() {
        $id = self::getUserId();
        $db = new db();
        $row = $db->selectOne('account', 'id', $id);
        return $row;
    }

    /**
     * method for getting all users
     * @return  array   $rows containing all users
     */
    public function getUsers($from = 0, $limit = 10) {
        $q = new q();
        $q->setSelect('account')->limit($from, $limit);
        $rows = $q->fetch();
        return $rows;
    }

    public function getNumUsers() {
        $db = new q();
        return $db->setSelectNumRows('account')->fetch();
    }

    /**
     * Updates a user from POST request
     * @return boolean $res true on success else false
     */
    public function updateEmailUser() {

        $values = array(
            'email' => $_POST['email'],
        );

        isset($_POST['admin']) ? $values['admin'] = 1 : $values['admin'] = 0;

        if (session::isSuper()) {
            isset($_POST['super']) ? $values['super'] = 1 : $values['super'] = 0;
        }

        isset($_POST['verified']) ? $values['verified'] = 1 : $values['verified'] = 0;
        isset($_POST['locked']) ? $values['locked'] = 1 : $values['locked'] = 0;

        if (strlen($_POST['password']) != 0) {
            $values['password'] = md5($_POST['password']);
        }

        if ($values['locked'] == 1) {
            $this->lockUser($this->id);

        }

        $db = new db();
        $res = $db->update('account', $values, $this->id);
        return $res;
    }

    /**
     * remove system cookie if any
     * @param type $user_id
     * @return type
     */
    public function lockUser($user_id) {
        $values = array ('locked' => 1);
        q::update('account')->values($values)->filter('id =', $user_id)->exec();
        q::delete('system_cookie')->filter('account_id =', $user_id)->exec();
    }


    public function searchAccount() {
        $v = new adminViews();
        $v->searchForm();
    }

    /**
     * method for updaing a user
     *
     * @return int $res
     */
    public function updateUrlUser() {
        isset($_POST['admin']) ? $values['admin'] = 1 : $values['admin'] = 0;

        if (session::isSuper()) {
            isset($_POST['super']) ? $values['super'] = 1 : $values['super'] = 0;
        }
        isset($_POST['verified']) ? $values['verified'] = 1 : $values['verified'] = 0;
        isset($_POST['locked']) ? $values['locked'] = 1 : $values['locked'] = 0;

        if ($values['locked'] == 1) {
            $this->lockUser($this->id);
            $this->lockProfile($this->id);
        }

        $db = new db();
        $res = $db->update('account', $values, $this->id);
        return $res;
    }
}
