<?php

template::setTitle(lang::translate('account_facebook_login'));
include_once config::getModulePath('account') . "/lib/facebook.inc";
include_module('account/iframe');
account_facebook_login();
return;
