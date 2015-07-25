<?php

$ary = array ();
$ary['default'] = array (
    'account_allow_edit' => "super",
    'account_show_menu' => "",
    'account_show_admin_link' => "super",
    'account_default_url' => "/account/login/index",
    'account_facebook_api_secret' => "",
    'account_facebook_api_appid' => 269965633048607,
    'account_disable_admin_interface' => 0,
    'account_admin_only' => 0,
    'account_logins' => ['email'],
    'account_auto_merge' => 1,
    'account_allow_create' => "anon"
);

$ary['development'] = array (
    'account_allow_create' => "user"
);

return $ary;