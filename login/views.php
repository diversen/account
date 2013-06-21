<?php

class account_login_views {
    
    public static function formCreate () {
        $options = array ();
        html::$autoLoadTrigger = 'submit';
        html::init($_POST);
        html::formStart('account_create_form');
        html::legend(lang::translate('account_user_create_legend'));
        html::label('email', lang::translate('account_email_label'), array('required' => 1));
        html::text('email',  null, $options);
        html::label('password', lang::translate('account_password_label'), array('required' => 1));
        html::password('password', null, $options);
        html::label('password2', lang::translate('account_retype_password_label'), array('required' => 1));
        html::password('password2', null, $options);

        event::triggerEvent(config::getModuleIni('account_events'),
            array ('action' => 'form'));

        html::label('captcha', captcha::createCaptcha());
        html::text('captcha');
        html::label('submit', '');
        html::submit('submit', lang::translate('account_submit'));
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
        html::legend(lang::translate('account_login_legend'));
        html::label('email', lang::translate('account_email_label'), array('required' => true));
        html::text('email');
        html::label('password', lang::translate('account_form_password'), array('required' => true));
        html::password('password');

        self::keepSession();

        html::label('submit', '');
        html::submit('submit_account_login', lang::translate('account_submit'));
        html::formEnd();
        echo html::getStr();
    }
    
    public static function keepSession () {

        $keep_session_label = lang::translate('account_keep_session');
        $days = config::getMainIni('cookie_time'); 

        if ($days > 0 ) {
            $keep_session_label.= ' ' .  $days . ' ' . lang::translate('account_num_days'); 

        }            
        html::label('keep_session', $keep_session_label);
        html::checkbox('keep_session');

    }
}
