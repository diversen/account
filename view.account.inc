<?php

/**
 * @package    account
 */


/**
 * displays a account keep session checkbox 
 */
/*
function view_account_keep_session () {
    $keep_session_label = lang::translate('account_keep_session');
    $days = config::getMainIni('cookie_time'); 

    if ($days > 0 ) {
        $keep_session_label.= ' ' .  $days . ' ' . lang::translate('account_num_days'); 
        
    }            
    html::label('keep_session', $keep_session_label);
    html::checkbox('keep_session');
}*/

/**
 * displays a account keep session checkbox 
 */
/*
function account_get_keep_session () {
    $keep_session_label = lang::translate('account_keep_session');
    $days = config::getMainIni('cookie_time'); 

    if ($days > 0 ) {
        $keep_session_label.= ' ' .  $days . ' ' . lang::translate('account_num_days'); 
        
    }            
    $str = html::labelClean('keep_session', $keep_session_label);
    $str.= html::checkboxClean('keep_session');
    return $str;
}*/


