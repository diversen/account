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
use diversen\user;
use diversen\strings\mb;
use diversen\view;
use diversen\mailsmtp;
use modules\configdb\module as configdb;

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
        if (!$this->getNumUserInfo()) {
            return;
        }
        
        $u = new \modules\content\users\module();
        if (!empty($_POST['submit'])) {
            $_POST = html::specialEncode($_POST);
            $this->validateCreate($_POST['email']);
            if (empty($this->errors)) {
                
                $res = $this->createUserSendEmail();
                if ($res) {
                    http::locationHeader(
                        '/account/admin/create', 
                        lang::translate('Account has been created. The created user will need to visit his mailbox'));
                } else {
                    echo html::getErrors($this->errors);
                }
            } else {
                echo html::getErrors($this->errors);
            }
        }

        echo self::formCreate();
    }
    
    /**
     * /account/admin/domain controller
     * Overrides account_domain_Allow setting
     * @return void
     */
    public function domainAction () {
        
        http::prg();
        if (!session::checkAccess('super')) {
            return;
        }
        
        if (isset($_POST['submit'])) {
            $c = new configdb();
            $c->set('account_domain_allow', html::specialDecode($_POST['domain']));
            http::locationHeader('/account/admin/domain', lang::translate('Allowed domain has been updated'));
        }
        
        $f = new html();
        $f->formStart();

        $current = conf::getModuleIni('account_domain_allow');
        $f->init(array('domain' => $current), 'submit', true);
        $f->legend(lang::translate('Only allow emails from this domain'));
        $f->label('domain', lang::translate('Enter domain, e.g. gmail.com'));
        $f->text('domain');
        $f->submit('submit', lang::translate('Update'));
        $f->formEnd();
        echo $f->get();
    }
    
    /**
     * /account/admin/domain controller
     * Overrides account_domain_Allow setting
     * @return void
     */
    public function usernumAction () {
        
        http::prg();
        if (!session::checkAccess('super')) {
            return;
        }
        
        if (isset($_POST['submit'])) {
            
            $_POST['usernum'] = (int)$_POST['usernum'];
            if (!$_POST['usernum']) {
                $_POST['usernum'] = 1;
            }
            
            $c = new configdb();
            $c->set('account_user_limit', $_POST['usernum']);
            http::locationHeader('/account/admin/usernum', lang::translate('Allowed number of users has been updated'));
        }
        
        $f = new html();
        $f->formStart();

        $current = conf::getModuleIni('account_user_limit');
        $f->init(array('usernum' => $current), 'submit', true);
        $f->legend(lang::translate('Max amount of accounts'));
        $f->label('usernum', lang::translate('Number of users'));
        $f->text('usernum');
        $f->submit('submit', lang::translate('Update'));
        $f->formEnd();
        echo $f->get();
    }
    
    
    
    /**
     * If 'account_user_limit' is set in account.ini we check how 
     * many user is allowed to be created
     * @return boolean $res
     */
    public function getNumUserInfo () {
        $num_limit = conf::getModuleIni('account_user_limit');
        
        if (!$num_limit) {
            return;
        }
        
        $num_verified = q::numRows('account')->
                filter('verified =', 1)->
                condition('AND')->
                filter('locked =', 0)->
                condition('AND')->
                filter('super !=', 1)->
                fetch();
        
        $str = lang::translate('Number of users') . ' ' . $num_verified . "<br />";
        $str.= lang::translate('Number of verified users') . ' ' . $num_verified . "<br />";
        $str.=lang::translate('You are allow to create a total of {num} users', array ('num' => $num_limit));
                
        if ($num_verified >= $num_limit) {
            echo html::getWarning($str);
            return false;
        } 
        
        
        echo html::getConfirm($str);
        return true;
    }
    
    
    
    
    public function createUserSendEmail () {
        // Set mail view
        
        $user_id = $this->createUserBegin($_POST['email']);
        if (!$user_id) {
            $this->errors[] = lang::translate('Could not create new user');
            return false;
        }
        
        $res = $this->createUserEmailCommit($user_id);
        if (!$res) {
            $this->errors[] = lang::translate('Could not send email. Try again later');
            return false;
        }
        
        return true;
        
        
    }
    
    
    /**
     * Send email and commit to databases on success
     * @param int $last_insert_id
     * @return mixed $res false or user row from database
     */
    public function createUserEmailCommit ($last_insert_id) {
        
        $account = user::getAccount($last_insert_id);
        $parts = $this->getMailParts($account['email']);
        
        $sent = mailsmtp::mail($account['email'], $parts['subject'], $parts['txt'], $parts['html']); 

        if (!$sent) {
            q::rollback();
            return false;
        } else {
            if (!q::commit()) {
                q::rollback();
                return false;
            }
        }
        return user::getAccount($last_insert_id);
    } 

    /**
     * Get email parts for email with notification about account creation
     * @param string $email
     * @return array $parts array ('subject' => 'subject', 'txt' => 'txt message', 'html' => 'html message')
     */
    public function getMailParts ($email) {
        $email = mb::tolower($email);
        $r = new \modules\account\requestpw\module();
        $vars = $r->getMailVarsFromEmail($email);
        
        $subject = lang::translate('Invitaion from the site ') . ' ' . conf::getSchemeWithServerName();
        $view = conf::getModulePath('account') . "/views/mails/signup_invite.inc";
        
        //$view = conf::getModulePath('content') . "/mail/mail.php";
        $txt = view::getFileView($view,  $vars);
        
        $helper = new \diversen\mailer\markdown();
        $html = $helper->getEmailHtml($subject, $txt);

        return array ('txt' => $txt, 'subject' => $subject, 'html' => $html);
    }
    

    /**
     * Create a new user in account table and sends an email
     * @return type
     */
    public function createUserBegin ($email) {
        

        $md5_key = random::md5();
        $c = new \modules\account\create\module();
        
        // Create invite user with no password
        q::begin();
        $res = $c->createDbUser($email, '', $md5_key, 1);
        
        if ($res) {
            return q::lastInsertId();
        } else {
            q::rollback();
            
            return false;
        }        
    }
    
    public function validateCreate ($email) {

        if (isset($_POST['submit'])) {
            $c = new \modules\account\create\module();
            $c->validateEmail($email);
            if (!empty($c->errors)) {
                $this->errors = $c->errors;
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
        \modules\account\admin\views::listUsers($users);
        
        echo $p->getPagerHTML();
    }
    
    public function searchAction () {
        $_GET = html::specialEncode($_GET);
        \modules\account\admin\views::searchForm();
       
        if (isset($_GET['submit'])) {
            
            $acc = new account();
            $res = $acc->searchIdOrEmail($_GET['id']);
            
            if (!empty($res)) {
                echo lang::translate('Found the following accounts');
                
                echo html::getHeadline(lang::translate('Search results'), 'h2');
                \modules\account\admin\views::listUsers($res);
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
        $user = $this->getUser(uri::fragment(3));
        
        if (!session::isSuper() && $user['super'] == 1) {
            echo html::getError(lang::translate('You can not edit a super account'));
            return;     
        }

        if (isset($_POST['submit'])) {
            if (empty($user['url'])) {
                $this->validate();
            }
            if (empty($this->errors)) {
                $res = $this->updateUser();
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

        \modules\account\admin\views::updateEmailUser($user);

    }




    /**
     * Validate and sets $error
     */
    function validate($options = null) {
        if (empty($_POST['submit'])) {
            return;
        }

    }

    /**
     * method for getting a user from url fragemtn
     *
     * @return array $row
     */
    public function getUser($id) {     
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

    /**
     * Return total count of users
     * @return int $res
     */
    public function getNumUsers() {
        return q::numRows('account')->fetch();
    }

    /**
     * Updates a user from POST request
     * @return boolean $res true on success else false
     */
    public function updateUser() {
        
        $values = [];
        
        isset($_POST['admin']) ? $values['admin'] = 1 : $values['admin'] = 0;
        isset($_POST['verified']) ? $values['verified'] = 1 : $values['verified'] = 0;
        isset($_POST['locked']) ? $values['locked'] = 1 : $values['locked'] = 0;

        $values = $this->setSuperValues($values);

        if ($values['locked'] == 1) {
            $this->lockUser(uri::fragment(3));
        }


        $db = new db();
        $res = $db->update('account', $values, uri::fragment(3));
        return $res;
    }
    
    /**
     * Set special values if logged in as super user
     * @param type $values
     */
    public function setSuperValues ($values) {
        
        if (session::isSuper()) {
            isset($_POST['super']) ? $values['super'] = 1 : $values['super'] = 0;
        }
        return $values;
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
}
