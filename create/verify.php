<?php

template::setTitle(lang::translate('account_title_verify_account'));
$a = new accountCreate();
$a->validate();
$res = $a->verifyAccount();
if (!$res){
    html::errors($a->errors);
} else if ($res === 2) {
    accountCreateViews::verify(lang::translate('account_is_already_verified'));
} else {
    accountCreateViews::verify(lang::translate('account_has_been_verified'));
}