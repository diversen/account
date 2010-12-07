<?php

/**
 * @package    account
 */
template::setTitle(lang::translate('Verify Account'));

$a = new accountCreate();
$res = $a->verifyAccount();
if (!$res){
    view_form_errors($a->errors);
} else if ($res === 2) {
    view_confirm(lang::translate("Account has already been verified. You may log in"));
} else {
    view_confirm(lang::translate("Account has been verified. You may log in"));
}

