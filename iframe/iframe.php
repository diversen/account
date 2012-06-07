<?php


include_once config::getModulePath('account') . "/lib/facebook.inc";

get_main_ini('server_name');
include_module('account/iframe');
include_module('account/facebook');
include_module('account/login');
$ary = array (
    'redirect_uri' => 'http://www.dev.sweetpoints.dk/account/iframe/iframe?close=1',
    'display'   => 'popup');
$url = facebook_get_login_url($ary);


 if (isset($_REQUEST['close'])){
        echo "<script>
            window.close();
            </script>";
}

print_r($_REQUEST);

?>

<script type="text/javascript">
    var newwindow;
    var intId;
    function login(){
        var  screenX    = typeof window.screenX != 'undefined' ? window.screenX : window.screenLeft,
             screenY    = typeof window.screenY != 'undefined' ? window.screenY : window.screenTop,
             outerWidth = typeof window.outerWidth != 'undefined' ? window.outerWidth : document.body.clientWidth,
             outerHeight = typeof window.outerHeight != 'undefined' ? window.outerHeight : (document.body.clientHeight - 22),
             width    = 600,
             height   = 400,
             //left     = parseInt(screenX + ((outerWidth - width) / 2), 10),
             //top      = parseInt(screenY + ((outerHeight - height) / 2.5), 10),
             left     = 0
             top      = 0
             features = (
                 'width=' + width +
                 ',height=' + height +
                 ',left=' + left +
                 ',top=' + top
             );
      
             newwindow=window.open('<?=$url?>','Login_by_facebook',features);
             if (window.focus) {
                 newwindow.focus()
             }
             return false;
        }
        
</script>
<a href="#" onclick="login();return false;">login</a>
<?php

http::prg();
accountLogin::controlLogin();
die;
