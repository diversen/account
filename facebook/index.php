<?php

/**
 * controller for logging in with facebook
 */
usleep(100000);
template::setTitle(lang::translate('Facebook Login'));
$options = array ('keep_session' => 1);
$fb = new account_facebook($options);
$fb->setAcceptUniqueOnlyEmail();
$fb->login();

if (!empty($fb->errors)) {
    html::errors($fb->errors);
}

