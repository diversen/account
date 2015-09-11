<?php

namespace modules\account\requestpw;
use diversen\captcha;
use diversen\html;
use diversen\lang;

class views {
    public static function formVerify () {
        $field_size = array ('size' => '30');
        html::$autoLoadTrigger = 'submit';
        html::init($_POST);
        html::formStart('account_set_new_password');
        html::legend(lang::translate('Enter new password'));
        html::label('password1', lang::translate('Password'));
        html::password('password1',  null, $field_size);
        html::label('password2', lang::translate('Repeat new password'));
        html::password('password2',  null, $field_size);
        html::label('submit', '');
        html::submit('submit', lang::translate('Send'));
        html::formEnd();
        echo html::getStr();
    }
    
    public static function formSend () {
        html::$autoLoadTrigger = 'submit';

        html::init($_POST);
        html::formStart('account_request_new_password');
        html::legend( lang::translate('Request new password') );
        html::label('email', lang::translate('Email'), array('required' => 1));
        html::text('email');
        html::label('captcha', lang::translate('Enter CAPTCHA string'), array ('required' => true));
        html::captcha();
        html::label('submit', '');
        html::submit('submit', lang::translate('Send'));
        html::formEnd();
        echo html::getStr();
    }
}