<?php

/**
 * File contains account_admin class which extends account create. 
 */

moduleloader::includeModule ('account');
moduleloader::includeModule ('account/create');
view::includeOverrideFunctions('account', 'admin/views.phtml');

/**
 * Class account_admin
 */
class account_admin extends account {

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
    function __construct(){
        $this->uri = uri::getInstance();
        $this->id = (int)$this->uri->fragment(3);
    }
    
    public static function test () {
        echo "hello world";
    }

    /**
     * get user id from URL
     * @return  int $user_id
     */
    public static function getUserId (){
        $uri = uri::getInstance();
        return (int)$uri->fragment(3);
    }

    /**
     * Validate and sets $error
     */
    function validate($options = null){
        if (empty($_POST['submit'])){
            return;
        }
        if (strlen($_POST['password']) < 7){
            $this->errors['password'] = lang::translate('account_password_error_length');
        }
        if (strlen($_POST['password']) == 0){
            unset($this->errors['password']);
        }
        if ($_POST['password'] != $_POST['password2']){
            $this->errors['password'] = lang::translate('account_password_dont_match');
        }
    }


    /**
     * method for getting a user from url fragemtn
     *
     * @return array $row
     */
    public function getUser(){
        $id = self::getUserId();
        $db = new db();
        $row = $db->selectOne('account', 'id', $id);
        return $row;
    }

    /**
     * method for getting all users
     * @return  array   $rows containing all users
     */
    public function getUsers($from = 0, $limit = 10){
        $q = new db_q();
        $q->setSelect('account')->limit($from, $limit);
        $rows = $q->fetch();
        return $rows;
    }
    
    
    
    public function getNumUsers () {
        $db = new db_q();
        return $db->setSelectNumRows('account')->fetch();
    }

    /**
     * method for updaing a user
     * @return boolean $res true on success else false
     */
    public function updateEmailUser (){
    
        $values = array(
            'email' => $_POST['email'],
        );

        isset($_POST['admin']) ? $values['admin'] = 1 : $values['admin'] = 0;
        isset($_POST['super']) ? $values['super'] = 1 : $values['super'] = 0;
        isset($_POST['verified']) ? $values['verified'] = 1 : $values['verified'] = 0;
        isset($_POST['locked']) ? $values['locked'] = 1 : $values['locked'] = 0;
        
        if ( strlen($_POST['password']) != 0){
            $values['password'] = md5($_POST['password']);
        }

        $db = new db();
        $res = $db->update('account', $values, $this->id);
        return $res;
    }
    
    public function searchAccount () {
        $v = new account_admin_views();
        $v->searchForm();
    }

    /**
     * method for updaing a user
     *
     * @return int affected rows
     */
    public function updateUrlUser (){      
        isset($_POST['admin']) ? $values['admin'] = 1 : $values['admin'] = 0;
        isset($_POST['super']) ? $values['super'] = 1 : $values['super'] = 0;
        isset($_POST['verified']) ? $values['verified'] = 1 : $values['verified'] = 0;
        isset($_POST['locked']) ? $values['locked'] = 1 : $values['locked'] = 0;
        $db = new db();
        $res = $db->update('account', $values, $this->id);
        return $res;
    }

    /**
     * method for deleting a user
     *
     * @return int  affected rows
     */
    public function deleteUser(){
        $db = new db();
        return $db->delete('account', 'id',  $this->id);
    }
}
class account_admin_module extends account_admin {}
