<?php

/**
 * controller for making api calls
 */
$git = new account_github();
$git->setAcceptUniqueOnlyEmail(true);
$git->auth ();

if (!empty($git->errors)) {
    html::errors($git->errors);
}
