<?php

/**
 * controller file admin/index
 * 
 * @package    account
 */
if (!session::checkAccessFromModuleIni('account_allow_edit')){
    return;
}

template::setTitle(lang::translate('Admin index'));


