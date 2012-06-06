<?php

include_module('account/facebook');
$app_id = config::getModuleIni('account_facebook_api_appid');
$facebook_str = file::getCachedFile(
        config::getModulePath('account') . "/assets/facebook.html");

$facebook_str = str_replace('YOUR_APP_ID', $app_id, $facebook_str);
$facebook_str = str_replace('LANGUAGE', config::getMainIni('language'),$facebook_str);

template::setStartHTML($facebook_str);

?>
<h3>New JavaScript SDK & OAuth 2.0 based FBConnect Tutorial | Thinkdiff.net</h3>
        <button id="fb-auth">Login</button>
        <div id="loader" style="display:none">
            <img src="ajax-loader.gif" alt="loading" />
        </div>
        <br />
        <div id="user-info"></div>
        <br />
        <div id="debug"></div>
        
        <div id="other" style="display:none">
            <a href="#" onclick="showStream(); return false;">Publish Wall Post</a> |
            <a href="#" onclick="share(); return false;">Share With Your Friends</a> |
            <a href="#" onclick="graphStreamPublish(); return false;">Publish Stream Using Graph API</a> |
            <a href="#" onclick="fqlQuery(); return false;">FQL Query Example</a>
            
            <br />
            <textarea id="status" cols="50" rows="5">Write your status here and click 'Status Set Using Legacy Api Call'</textarea>
            <br />
            <a href="#" onclick="setStatus(); return false;">Status Set Using Legacy Api Call</a>
        </div>
 <?php
 
 include_once config::getModulePath('account') . "/lib/facebook.inc";
include_module('account/iframe');

$profile = facebook_get_user_profile();
//$f = new accountFacebook();
print_r($profile);
