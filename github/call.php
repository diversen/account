<?php

die('test');
$api = new githubApi();
//$command = '/gists/3188233';
//$res = $api->apiCall($command);

// create a gist
$command = '/gists';
$request = 'POST';
$content = array (
    'description' => 'mmmmmmmm....',
    'public' => 'true',
    'files' => array (
        'file7.txt' => array (
            'content' => 'New content from api'
         ),
    ),
);


//https://gist.github.com/4381137
//$res = $api->apiCall('/gists/4380885', 'DELETE');
// $res = $api->apiCall('/gists', 'POST', $content);
//https://gist.github.com/4381137
//https://gist.github.com/4381068
// https://gist.github.com/4381068

$res = $api->apiCall('/gists/4381068', 'PATCH', $content);


echo $api->returnCode;
print_r($res);

die;