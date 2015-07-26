<?php

if (conf::getModuleIni('account_hide_terms')) {
    return;
}

$lang = conf::getMainIni('language');
$privacy = conf::getModulePath('account') .  "/views/terms/$lang/privacy.inc";
$privacy_default = conf::getModulePath('account') .  "/views/terms/en_GB/privacy.inc";


if (file_exists($privacy)) {
    echo view::get('account', "terms/$lang/privacy");
} else {
    echo view::getFileView($privacy_default);
}