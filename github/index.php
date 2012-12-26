<?php

include_once "coslib/github_api.php";

template::setTitle(lang::translate('account_login_or_logout'));
    
$login = new accountGithub();
$login->controlLogin();
