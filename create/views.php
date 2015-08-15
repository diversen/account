<?php

namespace modules\account\create;
use diversen\http;


class views {
    
    public static function verify ($message = null) {
        http::locationHeader('/account/login/index', $message);
    }    
}

