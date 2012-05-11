<?php

// Create our Application instance (replace this with your appId and secret).
// Create our Application instance.

session_destroy();
header ("Location: /account/facebook/index");
die;
$facebook = new Facebook(array(
  'appId'  => config::getModuleIni('account_facebook_api_appid'),
  'secret' => config::getModuleIni('account_facebook_api_secret'),
  /*'cookie' => true, */ 
));





    