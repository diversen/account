<?php

if (conf::getModuleIni('account_hide_terms')) {
    return;
}

$lang = conf::getMainIni('language');
$terms = conf::getModulePath('account') .  "/views/terms/$lang/terms.inc";
$terms_default = conf::getModulePath('account') .  "/views/terms/en_GB/terms.inc";

if (file_exists($terms)) {
    echo view::get('account', "terms/$lang/terms");
} else {
    echo view::getFileView($privacy_default);
}