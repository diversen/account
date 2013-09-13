<?php

template::setTitle(lang::translate('Verify Account'));
$a = new account_create();
$a->validate();
$res = $a->verifyAccount();
if (!$res){
    html::errors($a->errors);
} else if ($res === 2) {
    account_create_views::verify(lang::translate('Account is already verified'));
} else {
    account_create_views::verify(lang::translate('Account has been verified'));
}