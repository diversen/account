<?php

template::setTitle(lang::translate('Account'));

if (isset($_GET['return_to'])) {
    $_SESSION['return_to'] = rawurldecode($_GET['return_to']);
}

account_module::redirectDefault();
