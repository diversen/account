<?php

// Create our Application instance (replace this with your appId and secret).
// Create our Application instance.

$facebook = facebook_get_object();
$fb_key = 'fbs_'.config::getModuleIni('account_facebook_api_appid');
setcookie($fb_key, '', 0, '', '/', '');
//$facebook->setSession(NULL);
session_destroy();

header ("Location: /account/facebook/index");
die;