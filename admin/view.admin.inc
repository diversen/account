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

//template::setInlineCss(_COS_PATH . "/modules/account/assets/admin.css");

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
        html::legend(lang::translate('account_edit_legend'));
        html::label('email', lang::translate('account_email') );
        html::text('email');
        html::label('password', lang::translate('account_password') );
        html::password('password');
        html::label('password2', lang::translate('account_password_repeat') );
        html::password('password2');
        html::label('verified', lang::translate('account_is_verfied') );
        html::checkbox ('verified');
        html::label('admin', lang::translate('account_is_admin') );
        html::checkbox ('admin');
        html::label('super', lang::translate('account_is_super') );
        html::checkbox ('super');
        html::submit('submit', lang::translate('account_update'));
        html::formEnd();
        echo html::getStr();

   }

    function updateUrlUser ($values){

        html::$autoLoadTrigger = 'submit';
        html::init($values);
        html::formStart('account_form');
        html::legend(lang::translate('account_edit_legend'));
        html::label('verified', lang::translate('account_form_is_verified') );
        html::checkbox ('verified');
        html::label('admin', lang::translate('account_form_is_admin') );
        html::checkbox ('admin');
        html::label('super', lang::translate('account_form_is_super') );
        html::checkbox ('super');
        html::submit('submit', lang::translate('account_update'));
        html::formEnd();
        echo html::getStr();
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

        if ($v['admin']) {
            echo "<td>" . lang::translate('account_is_admin') . "</td>\n";
        } else {
            echo "<td>" . lang::translate('account_is_not_admin') . "</td>\n";
        }

        if ($v['super']) {
            echo "<td>" . lang::translate('account_is_super') . "</td>\n";
        } else {
            echo "<td>" . lang::translate('account_is_not_super'). "</td>\n";
        }

        if ($v['verified']) {
            echo "<td>" . lang::translate('account_is_verified') . "</td>\n";
        } else {
            echo "<td>" . lang::translate('account_is_not_verified') . "</td>";
        }
        echo "<td>" . $v['created'] . "</td>";
        echo "<td>" . html::createLink("/account/admin/edit/$v[id]", lang::translate('account_edit')) . "</td>\n";
        echo "<td>" . html::createLink("/account/admin/delete/$v[id]", lang::translate('account_delete')) . "</td>\n";
        echo "</tr>\n";
    }

    
      
    /**
     * function for view a delete user form
     *
     * @param array row with user
     */
    function delete ($values){
        html::formStart('account_form');
        html::legend(lang::translate('account_delete_account_legend'));

        html::submit('submit', lang::translate('account_delete'));
        html::formEnd();
        echo html::getStr();
    }
}