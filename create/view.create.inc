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
function view_create (){
    $field_size = array ('size' => '30');
    html::$autoLoadTrigger = 'submit';
    html::init($_POST);
    html::formStart('blog_form');
    html::legend(lang::translate('Get an user account by filling out the following form'));
    html::label('email', lang::translate('Email'));
    html::text('email',  '', $field_size);
    html::label('password', lang::translate('Password'));
    html::password('password', '', $field_size);
    html::label('password2', lang::translate('Retype Password'));
    html::password('password2', '', $field_size);
    html::label('captcha', captcha::createCaptcha());
    html::text('captcha', '', array ('size' => 1, 'maxlength'=> 2));
    html::label('submit', '');
    html::submit('submit', lang::translate('Submit'));
    html::formEnd();
    echo html::$formStr;
}