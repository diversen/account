<?php

/**
 * view file for account/create
 *
 * @package    account
 */

/**
 * include captcha lib
 */
include_once "captcha.php";

/**
 * function for showing a form for creating a user
 */
function account_view_create (){
    $_POST = html::specialEncode($_POST);
    $options = array ('size' => '30');
    html::$autoLoadTrigger = 'submit';
    html::init($_POST);
    html::formStart('blog_form');
    html::legend(lang::translate('account_user_create_legend'));
    html::label('email', lang::translate('account_email_label'));
    html::text('email',  null, $options);
    html::label('password', lang::translate('account_password_label'));
    html::password('password', null, $options);
    html::label('password2', lang::translate('account_retype_password_label'));
    html::password('password2', null, $options);
    html::label('captcha', captcha::createCaptcha());
    html::text('captcha');
    html::label('submit', '');
    html::submit('submit', lang::translate('account_submit'));
    html::formEnd();
    echo html::$formStr;
}