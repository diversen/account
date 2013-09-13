<?php

/**
 * controller for logging in with facebook
 */
usleep(100000);
template::setTitle(lang::translate('Facebook Login'));
$fb = new account_facebook();
$fb->setAcceptUniqueOnlyEmail();
$fb->login();

if (!empty($fb->errors)) {
    html::errors($fb->errors);
}

