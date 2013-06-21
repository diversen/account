<?php

template::setTitle(lang::translate('account_login_or_logout'));

usleep(100000);

// check to see if user is allowed to use github login
if (!in_array('github', config::getModuleIni('account_logins'))){
    moduleloader::setStatus(403);
    return;
}

$login = new account_github();
$login->setAcceptUniqueOnlyEmail(true);
$login->controlLogin();
