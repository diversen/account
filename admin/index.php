<?php

/**
 * controller file admin/index
 * 
 * @package    account
 */
if (!session::checkAccessControl('account_allow_edit')){
    return;
}

template::setTitle(lang::translate('account_index_title'));


