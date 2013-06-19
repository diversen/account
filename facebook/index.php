<?php

/**
 * controller for logging in with facebook
 */
usleep(100000);
template::setTitle(lang::translate('account_facebook_login'));
$fb = new accountFacebook();
$fb->setAcceptUniqueOnlyEmail();
$fb->login();

if (!empty($fb->errors)) {
    html::errors($fb->errors);
}

