<?php

$lang = config::getMainIni('language');
echo view::getFileView(config::getModulePath('account') .  "/lang/$lang/privacy.inc");