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
        
        $str = '<table class="uk-table">';
        $str.= '<thead><tr>';
        $str.= '<th>' . lang::translate('ID') . '</th>';
        $str.= '<th>' . lang::translate('Profile link') . '</th>';
        $str.= '<th>' . lang::translate('Email') . '</th>';
        $str.= '<th>' . lang::translate('Admin') . '</th>';
        $str.= '<th>' . lang::translate('Super') . '</th>';
        $str.= '<th>' . lang::translate('Verified') . '</th>';
        $str.= '<th>' . lang::translate('Edit') . '</th>';
        $str.= '</tr></thead>';
        
        foreach ($users as $user) {
            $str.='<tbody>';
            $str.= self::listUser( $user);
            $str.='</tbody>';
        }
        $str.='</table>';
        echo $str;
    }
    
    /**
     * list single user
     * @param array $user
     */
    public static function listUser ($user) {

        $str = '<tr>';
        $str.= '<td>';

        $str.= $user['id'];

        $str.= '</td>';        
        $str.= '<td>';
        
        $date = time::getDateString($user['created']);
        $str.= user::getProfileLink($user, $date, array ('user_id'));
        
        $str.= '</td>';
        $str.= '<td>';
        $str.= $user['email'];
        $str.= '</td>';

        $str.= '<td>';
        if ($user['admin']) {
            $str.= lang::translate('Admin user');
        } else {
            $str.= '';
        }
        
        $str.= '</td>';
        $str.= '<td>';
        
        if ($user['super']) {
            $str.= lang::translate('Super user');
        } else {
            $str.= '';
        }
        $str.= '</td>';
        $str.= '<td>';
        if ($user['verified']) {
            $str.= lang::translate('Verified');
        } else {
            $str.= '';
        }
        $str.='</td>';

        $str.='<td>';
        $str.= html::createLink("/account/admin/edit/$user[id]", lang::translate('Edit'));
        $str.='</td>';
        $str.='</tr>';
        return $str;
        //echo MENU_SUB_SEPARATOR;
        //$str.= html::createLink("/account/admin/delete/$user[id]", lang::translate('Delete'));

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
