<?php

template::setTitle(lang::translate('account_title_verify_account'));
$a = new account_create();
$a->validate();
$res = $a->verifyAccount();
if (!$res){
    html::errors($a->errors);
} else if ($res === 2) {
    account_create_views::verify(lang::translate('account_is_already_verified'));
} else {
    account_create_views::verify(lang::translate('account_has_been_verified'));
}