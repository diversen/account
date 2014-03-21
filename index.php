<?php

template::setTitle(lang::translate('Account'));

if (isset($_GET['return_to'])) {
    $_SESSION['return_to'] = rawurldecode($_GET['return_to']);
}

if (isset($_GET['message'])) {
    session::setActionMessage(rawurldecode($_GET['message']));
}

account_module::redirectDefault();
