<?php

class account_create_views {
    
    public static function verify ($message = null) {
        http::locationHeader('/account/login/index', $message);
    }    
}

