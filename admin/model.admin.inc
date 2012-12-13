<?php

/**
 * File contains accountAdmin class which extends account create. 
 */
moduleLoader::includeModel ('account');
moduleLoader::includeModel ('account/create');
include_once "coslib/pearPager.php";

view::includeOverrideFunctions('account', 'admin/views.phtml');

/**
 * Class accountAdmin
 */
class accountAdmin extends accountCreate {

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

    /*
    function validateUrlUser(){
        if (empty($_POST['submit'])){
            return;
        }
    }*/

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
    public function getUsers($from = 0){
        $limit = 10;
       
        $q = new dbQ();
        $q->setSelect('account')->limit($from, $limit);
        $rows = $q->fetch();
        return $rows;
    }
    
    public function getNumUsers () {
        $db = new dbQ();
        return $db->setSelectNumRows('account')->fetch();
    }

    /**
     * check if username exists
     *
     * @param   string      $username
     * @return  int         $num_rows
     */
    /*
    public function usernameExist($username){
        $query = "SELECT * FROM  `account` WHERE `username` = :username AND `id` <> :id";
        $sth = self::$dbh->prepare($query);
        $sth->execute(array(':username' => $username, ':id' => $this->id ));
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
        return count($rows);    
    }*/

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
    
        if ( strlen($_POST['password']) != 0){
            $values['password'] = md5($_POST['password']);
        }

        $db = new db();
        $res = $db->update('account', $values, $this->id);
        return $res;
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