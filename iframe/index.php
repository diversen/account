<?php

$layout = new layout('clean/test');

include_module('account/facebook');
$app_id = config::getModuleIni('account_facebook_api_appid');
$facebook_str = file::getCachedFile(
        config::getModulePath('account') . "/assets/facebook.html");

$facebook_str = str_replace('YOUR_APP_ID', $app_id, $facebook_str);
$facebook_str = str_replace('LANGUAGE', config::getMainIni('language'),$facebook_str);

template::setStartHTML($facebook_str);

template::setJs('/js/jquery.cookie.js', null, array ('head' => true));
subTemplate::printHeader();
//die;
$url = "/account/facebook/index";
?>
<script type="text/javascript">
    var newwindow;
    var intId;
    function openidlogin(){
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
<a href="#" onclick="openidlogin();return false;">login</a>

        <button id="fb-auth">Login</button>
        <div id="loader" style="display:none">
            <img src="ajax-loader.gif" alt="loading" />
        </div>
        
<?php
 
mainTemplate::printFooter();
die;

