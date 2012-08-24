<?php


$v = $vars;
$date = time::getDateString($v['created']);
        echo "<tr>\n";
        echo "<td colspan=\"6\">" . user::getProfile($v, $date) . "</td>";
        echo "</tr>\n";
        echo "<tr>\n";

        if ($v['admin']) {
            echo "<td>" . lang::translate('account_is_admin') . "</td>\n";
        } else {
            echo "<td>" . lang::translate('account_is_not_admin') . "</td>\n";
        }

        if ($v['super']) {
            echo "<td>" . lang::translate('account_is_super') . "</td>\n";
        } else {
            echo "<td>" . lang::translate('account_is_not_super'). "</td>\n";
        }

        if ($v['verified']) {
            echo "<td>" . lang::translate('account_is_verified') . "</td>\n";
        } else {
            echo "<td>" . lang::translate('account_is_not_verified') . "</td>";
        }
        echo "<td>" . $v['created'] . "</td>";
        echo "<td>" . html::createLink("/account/admin/edit/$v[id]", lang::translate('account_edit')) . "</td>\n";
        echo "<td>" . html::createLink("/account/admin/delete/$v[id]", lang::translate('account_delete')) . "</td>\n";
        echo "</tr>\n";