<?php

/**
 * model file for account/admin controller
 * 
 * @package    account
 */

/**
 * include model for creating users
 */
include_model ('account/create');

/**
 * class for doing admin options on users
 *
 * @package    account
 */
class accountAdmin extends accountCreate {

    /**
     *
     * @var array   errors
     */
    public $errors = array();

    /**
     *
     * @var object  holding URI instance
     */
    public $uri;

    /**
     *
     * @var int id of a user
     */
    public $id;

    /**
     * constructor. Creates URI instance and sets id
     */
    function __construct(){
        $this->uri = uri::getInstance();
        // the category id
        $this->id = (int)$this->uri->fragment(3);
    }
    /**
     * Validate and sets this->errors
     */
    function validate($options = null){

        if (empty($_POST['submit'])){
            return;
        }

        $pattern = "/^([a-zA-Z0-9._])+$/";
        if (!preg_match($pattern, $_POST['username'])){
            $this->errors['username'] =
            lang::translate('Username can only contain alphabetic chars and number and . and _');
        }
        if (strlen($_POST['username']) < 5){
            $this->errors['username'] =
            lang::translate('Username should be at least 5 chars long');
        }

        if ($this->usernameExist($_POST['username'])){
            $this->errors['username'] = lang::translate('Username exists. Pick another');
        }
        if (strlen($_POST['password']) < 5){
            $this->errors['password'] = lang::translate('Password should be at least 5 chars long');
        }
        if (strlen($_POST['password']) == 0){
            unset($this->errors['password']);
        }
        if ($_POST['password'] != $_POST['password2']){
            $this->errors['password'] = lang::translate('Passwords does not match');
        }
    }

    /**
     * Validate and sets this->errors
     */
    function validateUrlUser(){

        if (empty($_POST['submit'])){
            return;
        }
    }

    /**
     * method for getting a user. Uses $this->id
     *
     * @return mixed   array a user row from db or 0
     */
    public function getUser(){
        try {
            $row = $this->selectOne('account', 'id', $this->id);
            return $row;
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
    }

    /**
     * method for getting all users
     *
     * @return  array   rows containing all users
     */
    public function getUsers(){
        try {
            $query = "SELECT * FROM `account` WHERE `username` != ''";
            $rows = $this->selectQuery($query);
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
        return $rows;
    }

/**
     * method for getting all users
     *
     * @return  array   rows containing all users
     */
    public function getOpenidUsers(){
        try {
            $query = "SELECT * FROM `account` WHERE `openid` != 'NULL'";
            $rows = $this->selectQuery($query);
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
        return $rows;
    }

    /**
     * method for checking if a username exists
     *
     * @param   string      fetch the row where username = $username
     * @return  array|false assoc row with user or false
     */
    public function usernameExist($username){
        try {
            $query = "SELECT * FROM  `account` WHERE `username` = :username AND `id` <> :id";
            $sth = self::$dbh->prepare($query);
            $sth->execute(array(':username' => $username, ':id' => $this->id ));
            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
            return count($rows);
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
    }

    /**
     * method for updaing a user
     *
     * @return int affected rows
     */
    public function updateEmailUser (){
        if (isset($_POST['admin']) && $_POST['admin'] == 1){
            $is_admin = 1;
        } else {
            $is_admin = 0;
        }

        if (isset($_POST['super']) && $_POST['super'] == 1){
            $is_super = 1;
        } else {
            $is_super = 0;
        }
        try {
            $values =
                array('email' => $_POST['email'], 'username'=> $_POST['username'], 'admin' => $is_admin, 'super' => $is_super
                );


            if ( strlen($_POST['password']) != 0){
            $values =
                array(  'email' => $_POST['email'],
                        'username'=> $_POST['username'],
                        'password' => md5($_POST['password']),
                        'admin' => $is_admin,
                        'super' => $is_super

                );
            }
            $res = $this->update('account', $values, $this->id);
            return $res;
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
    }

    /**
     * method for updaing a user
     *
     * @return int affected rows
     */
    public function updateUrlUser (){
        if (isset($_POST['admin']) && $_POST['admin'] == 1){
            $is_admin = 1;
        } else {
            $is_admin = 0;
        }

        if (isset($_POST['super']) && $_POST['super'] == 1){
            $is_super = 1;
        } else {
            $is_super = 0;
        }
        try {
            $values =
                array(  'admin' => $is_admin, 'super' => $is_super
                );



            $values =
                array(  
                        'admin' => $is_admin,
                        'super' => $is_super

                );

            $res = $this->update('account', $values, $this->id);
            return $res;
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
    }

    /**
     * method for deleting a user
     *
     * @return int  affected rows
     */
    public function deleteUser(){
        return $this->delete('account', 'id',  $this->id);
    }
}
