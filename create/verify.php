<?php

template::setTitle(lang::translate('account_title_verify_account'));
$a = new accountCreate();
$a->validate();
$res = $a->verifyAccount();
if (!$res){
    view_form_errors($a->errors);
} else if ($res === 2) {
    account_create_verify(lang::translate('account_is_already_verified'));
    //view_confirm();
} else {
    account_create_verify(lang::translate('account_has_been_verified'));
    //view_confirm();
}