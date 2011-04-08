<?php

template::setTitle(lang::translate('account_title_verify_account'));
$a = new accountCreate();
$res = $a->verifyAccount();
if (!$res){
    view_form_errors($a->errors);
} else if ($res === 2) {
    view_confirm(lang::translate('account_is_already_verified'));
} else {
    view_confirm(lang::translate('account_has_been_verified'));
}