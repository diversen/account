<?php

html::$autoLoadTrigger = 'submit';
html::init($_POST);
html::formStart('account_request_new_password');
html::legend(lang::translate('account_request_password_title'));
html::label('email', lang::translate('account_email_label'), array('required' => 1));
html::text('email');
html::label('captcha', captcha::createCaptcha());
html::text('captcha');
html::label('submit', '');
html::submit('submit', lang::translate('account_submit'));
html::formEnd();
echo html::getStr();
