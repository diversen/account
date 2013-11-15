<?php

template::setTitle(lang::translate('Account'));

if (isset($_GET['return_to'])) {
    $_SESSION['redirect_on_login'] = rawurldecode($_GET['return_to']);
}

account_module::redirectDefault();
