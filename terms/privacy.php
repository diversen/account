<?php

if (config::getModuleIni('account_hide_terms')) {
    return;
}

$lang = config::getMainIni('language');
$privacy = config::getModulePath('account') .  "/lang/$lang/privacy.inc";
$privacy_default = config::getModulePath('account') .  "/lang/en_GB/privacy.inc";

if (file_exists($privacy)) {
    echo view::getFileView($privacy);
} else {
    echo view::getFileView($privacy_default);
}