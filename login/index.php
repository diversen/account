<?php

usleep(100000);

http::prg();
template::setTitle(lang::translate('Log in or Log out'));

$options = array();
if (isset($_POST['keep_session']) && $_POST['keep_session'] == 1) {
    $options['keep_session'] = 1;
}

$options['auth_verified_only'] = 1;
    
$login = new account_login($options);
$login->controlLogin();
