<?php

/**
 * view file for creating email login 
 */
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
        
view_account_keep_session();
        
html::label('submit', '');
html::submit('submit_account_login', lang::translate('account_submit'));
html::formEnd();
echo html::getStr();
