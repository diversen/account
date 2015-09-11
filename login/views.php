<?php

namespace modules\account\login;

use diversen\conf;
use diversen\html;
use diversen\lang;

class views {
    
    public static function formCreate () {
        $options = array ();
        html::$autoLoadTrigger = 'submit';
        html::init($_POST);
        html::formStart('account_create_form');
        html::legend(lang::translate('Create account'));
        html::label('email', lang::translate('Email'), array('required' => 1));
        html::text('email',  null, $options);
        html::label('password', lang::translate('Password'), array('required' => 1));
        html::password('password', null, $options);
        html::label('password2', lang::translate('Repeat password'), array('required' => 1));
        html::password('password2', null, $options);
        html::label('captcha', lang::translate('Enter CAPTCHA string'), array ('required' => true));
        html::captcha();
        html::label('submit', '');
        html::submit('submit', lang::translate('Send'));
        html::formEnd();
        echo html::getStr();
    }
    
    public static function formLogin ($vars = null) {

        if (!isset($_POST['submit_account_login'])){
            $init = array ('keep_session' => 'on');
        } else {
            $init = html::specialEncode($_POST);
        }

        if (isset($vars['errors'])) {
            html::errors($vars['errors']);
        }

        html::$autoLoadTrigger = 'submit_account_login';
        html::init($init);
        html::formStart('account_request_new_password');
        html::legend(lang::translate('Login'));
        html::label('email', lang::translate('Email'), array('required' => true));
        html::text('email');
        html::label('password', lang::translate('Password'), array('required' => true));
        html::password('password');

        self::keepSession();

        html::label('submit', '');
        html::submit('submit_account_login', lang::translate('Send'));
        html::formEnd();
        echo html::getStr();
    }
    
    public static function keepSession () {

        $keep_session_label = lang::translate('Let me stay logged in');
        $days = conf::getMainIni('cookie_time'); 

        if ($days > 0 ) {
            $keep_session_label.= ' ' .  $days . ' ' . lang::translate('days'); 

        }            
        html::label('keep_session', $keep_session_label);
        html::checkbox('keep_session');

    }
}
