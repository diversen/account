<?php

/**
 * view file for account/admin
 * @package    account
 */

/**
 * include captcha lib.
 */
include_once "html.php";
include_once "captcha.php";

template::setInlineCss(_COS_PATH . "/modules/account/assets/admin.css");

class viewAccountAdmin {

   /**
    * function for updating a user
    *
    * @param array row to use when updating a user
    */
    function updateEmailUser ($values){

        if (!isset($_POST['submit'])){
            $values['password'] = '';
            $values['password2'] = '';
        } 

        html::$autoLoadTrigger = 'submit';
        html::init($values);
        html::formStart('account_form');
        html::legend(lang::translate('Edit User Account'));
        html::label('username', lang::translate('Username') );
        html::text('username');
        html::label('email', lang::translate('Email') );
        html::text('email');
        html::label('password', lang::translate('Password') );
        html::password('password');
        html::label('password2', lang::translate('Retype Password') );
        html::password('password2');
        html::label('verified', lang::translate('Is Verified') );
        html::checkbox ('verified');
        html::label('admin', lang::translate('Is Admin') );
        html::checkbox ('admin');
        html::label('super', lang::translate('Is Super') );
        html::checkbox ('super');
        html::submit('submit', 'Update');
        html::formEnd();
        echo html::$formStr;

   }

    function updateUrlUser ($values){

        html::$autoLoadTrigger = 'submit';
        html::init($values);
        html::formStart('account_form');
        html::legend(lang::translate('Edit User Account'));
        html::label('verified', lang::translate('Is Verified') );
        html::checkbox ('verified');
        html::label('admin', lang::translate('Is Admin') );
        html::checkbox ('admin');
        html::label('super', lang::translate('Is Super') );
        html::checkbox ('super');
        html::submit('submit', 'Update');
        html::formEnd();
        echo html::$formStr;
    }



    /**
     * view function for listing users
     *
     * @param array array of users
     */
    function users($users){
        echo "<div >\n";
        echo "<table class=\"account_list\">\n";
        foreach ($users as $k => $v) {
            self::viewUser($v);
        }
        echo "</table>\n";
        echo "</div>\n";
    }


    public static function viewUser ($v) {
        echo "<tr>\n";
        echo "<td colspan=\"6\">" . get_profile_link($v, array('uniqid' => true)) . "</td>";
        echo "</tr>\n";
        echo "<tr>\n";
        //echo "<td>$v[email]</td>\n";

        if ($v['admin']) {
            echo "<td>" . lang::translate('Admin') . "</td>\n";
        } else {
            echo "<td>" . lang::translate('Not Admin') . "</td>\n";
        }

        if ($v['super']) {
            echo "<td>" . lang::translate('Super') . "</td>\n";
        } else {
            echo "<td>" . lang::translate('Not Super'). "</td>\n";
        }

        if ($v['verified']) {
            echo "<td>" . lang::translate('Verified') . "</td>\n";
        } else {
            echo "<td>" . lang::translate('Not Verified') . "</td>";
        }
        echo "<td>" . $v['created'] . "</td>";
        echo "<td>" . create_link("/account/admin/edit/$v[id]", lang::translate('Edit')) . "</td>\n";
        echo "<td>" . create_link("/account/admin/delete/$v[id]", lang::translate('Delete')) . "</td>\n";
        echo "</tr>\n";
    }

    
      
    /**
     * function for view a delete user form
     *
     * @param array row with user
     */
    function delete ($values){
        html::formStart('account_form');
        html::legend(lang::translate('Delete User Account'));
        html::label('verified', lang::translate('Delete') );
        html::submit('submit', 'Delete');
        html::formEnd();
        echo html::$formStr;
    }
}