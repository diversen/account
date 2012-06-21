<?php

http::prg();
template::setTitle(lang::translate('account_login_or_logout'));

$options = array ('auth_verified_only' => 1);
$login = new accountLogin($options);
$login->controlLogin();