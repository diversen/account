<?php

    echo base64_encode("\000email@example.com\000password");die;
echo base64_encode("\000dennis@os-cms.dk\000iversen1234");
die;
$subject = "Her er en test";

$message = "Her er en besked fra Dennis. Håber alt er vel!";
$res = mail_utf8('dennisbech@yahoo.dk', $subject, $message);
var_dump($res);
//$res = mail_html($sub['email'], $mail['title'], $html);