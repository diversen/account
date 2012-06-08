<?php

template::setTitle(lang::translate('account_facebook_login'));
include_once config::getModulePath('account') . "/lib/facebook.inc";
account_facebook_login();
return;
