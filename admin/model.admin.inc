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

    public static function getUserId (){
        $uri = uri::getInstance();
        // the category id
        return (int)$uri->fragment(3);
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
        $id = self::getUserId();
        $row = $this->selectOne('account', 'id', $id);
        return $row;
    }

    /**
     * method for getting all users
     *
     * @return  array   rows containing all users
     */
    public function getUsers(){
        $query = "SELECT * FROM `account`";
        $rows = $this->selectQuery($query);
        return $rows;
    }

    /**
     * method for checking if a username exists
     *
     * @param   string      fetch the row where username = $username
     * @return  array|false assoc row with user or false
     */
    public function usernameExist($username){
        $query = "SELECT * FROM  `account` WHERE `username` = :username AND `id` <> :id";
        $sth = self::$dbh->prepare($query);
        $sth->execute(array(':username' => $username, ':id' => $this->id ));
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
        return count($rows);    
    }

    /**
     * method for updaing a user
     *
     * @return int affected rows
     */
    public function updateEmailUser (){
    
        $values = array(
            'email' => $_POST['email'],
            'username'=> $_POST['username'],
        );

        isset($_POST['admin']) ? $values['admin'] = 1 : $values['admin'] = 0;
        isset($_POST['super']) ? $values['super'] = 1 : $values['super'] = 0;
        isset($_POST['verified']) ? $values['verified'] = 1 : $values['verified'] = 0;
    
        if ( strlen($_POST['password']) != 0){
            $values['password'] = md5($_POST['password']);
        }

        $res = $this->update('account', $values, $this->id);
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
        $res = $this->update('account', $values, $this->id);
        return $res;
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
