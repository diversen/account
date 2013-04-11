<?php

template::setTitle(lang::translate('account_login_or_logout'));
    
$login = new accountGithub();
$login->setAcceptUniqueOnlyEmail(true);
$login->controlLogin();
