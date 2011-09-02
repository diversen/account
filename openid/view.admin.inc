<?php


include_once "lib/captcha.php";

/**
 * view form for editing a user
 * @param array $values
 */
function view_update ($values){
    if (isset($_POST['submit'])){
        $values = $_POST;
        
    } else {
        $values['password'] = '';
        $values['password2'] = '';
    }
    if (isset($values['admin']) && $values['admin'] != 0) {
        $checked = 'checked';
    } else {
        $checked = '';
    }

    if (isset($values['super']) && $values['super'] != 0) {
        $super_checked = 'checked';
    } else {
        $super_checked = '';
    }


    ?>
<div id="form_main">
<form method="post" action="">
<fieldset>
<legend><?php echo lang::translate('account_legend_edit_account') ?></legend>
<label for="openid"><?php echo lang::translate('account_form_openid')?>:</label><br />
<input type="text" name="openid" size="60" value="<?php echo htmlspecialchars($values['openid'])?>"><br />
<label for="username"><?php echo lang::translate('account_form_username')?>:</label><br />
<input type="text" name="username" value="<?php echo htmlspecialchars($values['username'])?>"><br />
<label for="username"><?php echo lang::translate('account_form_email')?>:</label><br />
<input type="text" name="email" value="<?php echo htmlspecialchars($values['email'])?>"><br />
<label for="password"><?php echo lang::translate('account_form_password')?>:</label><br />
<input type="password" name="password" value="<?php echo htmlspecialchars($values['password'])?>"><br />
<label for="password2"><?php echo lang::translate('account_form_password_retype')?>:</label><br />
<input type="password" name="password2" value="<?php echo htmlspecialchars($values['password2'])?>"><br />
<label for="admin"><?php echo lang::translate('account_is_super')?>:</label><br />
<input type="checkbox" name="admin" value="1" <?php echo $checked; ?>><br />
<label for="super"><?php echo lang::translate('account_is_admin')?>:</label><br />
<input type="checkbox" name="super" value="1" <?php echo $super_checked; ?>><br />
<input type="submit" name="submit" value="<?php echo lang::translate('account_form_submit')?>">
</fieldset>
</form>
</div>
<?php

}


/**
 * view function for listing users
 *
 * @param array array of users
 */
function view_users($users){ ?>
<!-- view_users -->
<div>
<table><?php
    foreach ($users as $k => $v) { ?>
<tr>
<td><?php echo $v['username']?></td><td><?php echo $v['email']?></td><?php
    if ($v['admin']) { ?>
    <td><?php echo lang::translate('account_is_admin')?></td><?php } else { ?>
    <td><?php echo lang::translate('account_is_not_admin')?></td><?php }
        if ($v['verified']) { ?>
    <td><?php echo lang::translate('account_is_verified')?></td><?php } else { ?>
    <td><?php echo lang::translate('account_is_not_verified')?></td><?php } ?>
    <td><?php echo $v['created']?></td>
    <td><?php echo create_link("/account/admin/edit/$v[id]", lang::translate('account_edit'))?></td>
    <td><?php echo create_link("/account/admin/delete/$v[id]", lang::translate('account_delete'))?></td>
</tr>
<?php } ?>
</table>
</div>
<!-- / view_users -->
<?php

}

/**
 * function for view a delete user form
 * 
 * @param array row with user
 */
function view_delete ($values){ ?>
<div id="form_main">
<form method="post" action="">
<fieldset>
<legend><?php echo lang::translate('account_delete_user_legend')?>:<?php echo $values['username']?></legend>
<p><input type="submit" name="submit" value="<?php echo lang::translate('account_delete')?>" /></p>
</fieldset>
</form>
</div>
<?php

}
