<?php

$options = array ();
html::$autoLoadTrigger = 'submit';
html::init($_POST);
html::formStart('blog_form');
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
