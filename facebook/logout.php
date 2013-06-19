<?php

/**
 * controller for logging out of facebook
 */

$facebook = facebook_get_object();
$fb_key = 'fbs_'.config::getModuleIni('account_facebook_api_appid');
setcookie($fb_key, '', 0, '', '/', '');
session_destroy();

http::locationHeader ("/account/facebook/index");
