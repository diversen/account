<?php

template::setTitle(lang::translate('account_facebook_login'));
$fb = new accountFacebook();
$fb->setAcceptUniqueOnlyEmail();
$fb->login();

account_facebook_login();
