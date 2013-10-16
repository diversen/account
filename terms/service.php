<?php

if (config::getModuleIni('account_hide_terms')) {
    return;
}

$lang = config::getMainIni('language');
$terms = config::getModulePath('account') .  "/views/terms/$lang/terms.inc";
$terms_default = config::getModulePath('account') .  "/views/terms/en_GB/terms.inc";

if (file_exists($terms)) {
    echo view::get('account', "terms/$lang/terms");
} else {
    echo view::getFileView($privacy_default);
}