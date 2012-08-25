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
