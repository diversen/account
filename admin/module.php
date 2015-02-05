<?php

use diversen\pagination as pearPager;

/**
 * File contains account_admin class which extends account create. 
 */
moduleloader::includeModule('account');
moduleloader::includeModule('account/create');
view::includeOverrideFunctions('account', 'admin/views.php');

/**
 * Class account_admin
 */
class account_admin extends account {


    /**
     * action
     * /account/admin/delete
     * @return type
     */
    public function deleteAction() {

        if (!session::checkAccess('super')) {
            return;
        }

        template::setTitle(lang::translate('Delete account'));
        $user = $this->getUser();

        if (!empty($_POST['submit'])) {
            $l->deleteUser();
            http::locationHeader(
                    '/account/admin/list', lang::translate('Account has been deleted'));
        } else {
            account_admin_views::delete($user);
        }
    }

    /**
     * list action
     * /account/admin/list
     * @return type
     */
    public function listAction() {
        
        if (!session::checkAccess('super')) {
            return;
        }

        $_GET = html::specialEncode($_GET);

        $this->searchAccount();
        if (isset($_GET['submit'])) {
            $acc = new account();
            $res = $acc->searchIdOrEmail($_GET['id']);
            if (!empty($res)) {
                echo lang::translate('Found the following accounts');
                account_admin_views::listUsers($res);
                echo "<hr />\n";
            } else {
                echo lang::translate('I could not find any matching results');
            }
        }

        $num_rows = $this->getNumUsers();
        $p = new pearPager($num_rows);
        $users = $this->getUsers($p->from);
        template::setTitle(lang::translate('Search for users'));
        account_admin_views::listUsers($users);
        echo $p->getPagerHTML();
    }

    /**
     * edit action
     * @return /account/admin/edit
     */
    public function editAction() {
        if (!session::checkAccess('super')) {
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
            account_admin_views::updateUrlUser($user);
        } else {
            account_admin_views::updateEmailUser($user);
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

    public static function test() {
        echo "hello world";
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
        $q = new db_q();
        $q->setSelect('account')->limit($from, $limit);
        $rows = $q->fetch();
        return $rows;
    }

    public function getNumUsers() {
        $db = new db_q();
        return $db->setSelectNumRows('account')->fetch();
    }

    /**
     * method for updaing a user
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
            $this->lockProfile($this->id);
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
        return db_q::delete('system_cookie')->filter('account_id =', $user_id)->exec();
    }

    /**
     * locks (actually deletes the account profile if any profile system is in place
     * @return type
     */
    public function lockProfile($user_id) {
        $profile_system = config::getMainIni('profile_module');
        if (!$profile_system) {
            return;
        }

        // just delete profile info
        $api_module = $profile_system . "/api";
        moduleloader::includeModule($api_module);
        $class = $profile_system . "_api_module";

        if (method_exists($class, 'lock')) {
            return $class::lock($user_id);
        }
    }

    public function searchAccount() {
        $v = new account_admin_views();
        $v->searchForm();
    }

    /**
     * method for updaing a user
     *
     * @return int affected rows
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

    /**
     * method for deleting a user
     *
     * @return int  affected rows
     */
    public function deleteUser() {
        $db = new db();
        return $db->delete('account', 'id', $this->id);
    }

}

class account_admin_module extends account_admin {
    
}
