<?php

http::prg();
template::setTitle(lang::translate('account_login_or_logout'));
$login = new accountLogin();
$login->controlLogin();