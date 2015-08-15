<?php

namespace modules\account\admin;

use diversen\html;
use diversen\lang;
use diversen\session;
use diversen\time;
use diversen\user;

class views {

   /**
    * function for updating a user
    *
    * @param array row to use when updating a user
    */
    public static function updateEmailUser ($values){
        echo "ARGH";
        if (!isset($_POST['submit'])){
            $values['password'] = '';
            $values['password2'] = '';
        } 

        html::$autoLoadTrigger = 'submit';
        html::init($values);
        html::formStart('account_form');
        html::legend(lang::translate('Edit account'));
        html::label('email', lang::translate('Email') );
        html::text('email');
        html::label('password', lang::translate('Password') );
        html::password('password');
        html::label('password2', lang::translate('Repeat password') );
        html::password('password2');
        html::label('verified', lang::translate('Account is verified') );
        html::checkbox ('verified');
        
        if (session::isSuper()) {
            html::label('super', lang::translate('Account is super') );
            html::checkbox ('super');
        }
        
        html::label('admin', lang::translate('Account is admin') );
        html::checkbox ('admin');
        
        html::label('locked', lang::translate('Account is locked') );
        html::checkbox ('locked');
        html::submit('submit', lang::translate('Update account'));
        html::formEnd();
        echo html::getStr();

   }

    public static function updateUrlUser ($values){

        html::$autoLoadTrigger = 'submit';
        html::init($values);
        html::formStart('account_form');
        html::legend(lang::translate('Edit account'));
        html::label('verified', lang::translate('Account is verified') );
        html::checkbox ('verified');
        
        if (session::isSuper()) {
            html::label('super', lang::translate('Account is super') );
            html::checkbox ('super');
        }
        
        html::label('admin', lang::translate('Account is admin') );
        html::checkbox ('admin');
        html::label('locked', lang::translate('Account is locked') );
        html::checkbox ('locked');
        html::submit('submit', lang::translate('Update account'));
        html::formEnd();
        echo html::getStr();
    }
   
    /**
     * function for view a delete user form
     *
     * @param array row with user
     */
    public static function delete ($values){
        html::formStart('account_form');
        html::legend(lang::translate('Delete account'));
        html::submit('submit', lang::translate('Delete account'));
        html::formEnd();
        echo html::getStr();
    }
    
    /**
     * list all users
     * @param array $users
     */
    public static function listUsers ($users) {
        foreach ($users as $user) {
            echo self::listUser( $user);
        }
    }
    
    /**
     * list single user
     * @param array $user
     */
    public static function listUser ($user) {

        $date = time::getDateString($user['created']);

        echo "<div class=\"account_admin_user\">\n";
        
        
        echo lang::translate('ID') . MENU_SUB_SEPARATOR_SEC . $user['id'];
        echo "<br />";
        echo user::getProfileLink($user, $date, array ('user_id'));
        echo "<br />";
            
        echo lang::translate('Email') . MENU_SUB_SEPARATOR_SEC . $user['email'];
        echo "<br />";


        if ($user['admin']) {
            echo lang::translate('Account is admin');
        } else {
            echo lang::translate('Account is not admin');
        }
        echo "<br />\n";

        if ($user['super']) {
            echo lang::translate('Account is super user');
        } else {
            echo lang::translate('Account is not super user');
        }
        echo "<br />\n";

        if ($user['verified']) {
            echo lang::translate('Account is verified');
        } else {
            echo lang::translate('Account is not verified');
        }
        echo "<br />\n";

        echo html::createLink("/account/admin/edit/$user[id]", lang::translate('Edit'));
        //echo MENU_SUB_SEPARATOR;
        //echo html::createLink("/account/admin/delete/$user[id]", lang::translate('Delete'));
        echo "<br />\n";
        echo "<br />\n";
        echo "</div>\n";
    }
    
    /**
     * user search form 
     */
    public static function searchForm () {
        $f = new html ();
        $f->formStart('form_search', 'get');
        $f->legend(lang::translate('Search accounts'));
        $f->init(null, 'submit');
        $f->label('id', lang::translate('Search account ID'));
        $f->text('id');
        $f->submit('submit', lang::translate('Search'));
        $f->formEnd();
        echo $f->getStr();
                
    }
}
