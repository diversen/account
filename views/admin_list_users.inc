<?php

$users = $vars;

        echo "<div >\n";
        echo "<table class=\"account_list\">\n";
        foreach ($users as $k => $v) {
            //self::viewUser($v);
            echo view::get('account', 'admin_list_user', $v);
        }
        echo "</table>\n";
        echo "</div>\n";