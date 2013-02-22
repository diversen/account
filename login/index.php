<?php

usleep(100000);

http::prg();
template::setTitle(lang::translate('account_login_or_logout'));

$options = array();
if (isset($_POST['keep_session']) && $_POST['keep_session'] == 1) {
    $options['keep_session'] = 1;
}

$options['auth_verified_only'] = 1;
    
$login = new accountLogin($options);
$login->controlLogin();
