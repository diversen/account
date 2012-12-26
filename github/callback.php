<?php

$redirect_uri = config::getSchemeWithServerName() . "/account/github/callback";
$post = array (
    'redirect_uri' => $redirect_uri,
    'client_id' => config::getModuleIni('account_github_id'),
    'client_secret' => config::getModuleIni('account_github_secret'),
);
//echo $_SESSION['state']; die;
$api = new githubApi();
$res = $api->setAccessToken($post);

if ($res) {
    http::locationHeader('/account/github/api');
} else {
    echo "Could not get access token. Errors: <br />";
    print_r($api->errors);
}