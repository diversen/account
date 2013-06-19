<?php

/**
 * controller for making api calls
 */
$git = new accountGithub();
$git->setAcceptUniqueOnlyEmail(true);
$git->auth ();

if (!empty($git->errors)) {
    html::errors($git->errors);
}
