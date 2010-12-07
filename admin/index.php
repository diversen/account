<?php

/**
 * controller file admin/index
 * 
 * @package    account
 */
if (!session::checkAccessControl('allow_edit')){
    return;
}

template::setTitle(lang::translate('Account Admin'));


