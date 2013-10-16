<?php

if (config::getModuleIni('account_hide_terms')) {
    return;
}

$lang = config::getMainIni('language');


$terms = config::getModulePath('account') .  "/lang/$lang/terms.inc";
$terms_default = config::getModulePath('account') .  "/lang/en_GB/terms.inc";

if (file_exists($terms)) {
    echo view::getFileView($terms);
} else {
    echo view::getFileView($terms_default);
}
