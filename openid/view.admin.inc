<?php



/**
 * view file for account/admin
 * @package    account
 */

/**
 * include captcha lib
 */
include_once "lib/captcha.php";
/**
 * function for updating a user
 *
 * @param array row to use when updating a user
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
<legend><?php echo lang::translate('Edit User Account') ?></legend>
<label for="openid"><?php echo lang::translate('openid')?>:</label><br />
<input type="text" name="openid" size="60" value="<?php echo htmlspecialchars($values['openid'])?>"><br />
<label for="username"><?php echo lang::translate('Username')?>:</label><br />
<input type="text" name="username" value="<?php echo htmlspecialchars($values['username'])?>"><br />
<label for="username"><?php echo lang::translate('Email')?>:</label><br />
<input type="text" name="email" value="<?php echo htmlspecialchars($values['email'])?>"><br />
<label for="password"><?php echo lang::translate('Password')?>:</label><br />
<input type="password" name="password" value="<?php echo htmlspecialchars($values['password'])?>"><br />
<label for="password2"><?php echo lang::translate('Retype Password')?>:</label><br />
<input type="password" name="password2" value="<?php echo htmlspecialchars($values['password2'])?>"><br />
<label for="admin"><?php echo lang::translate('Is Admin')?>:</label><br />
<input type="checkbox" name="admin" value="1" <?php echo $checked; ?>><br />
<label for="super"><?php echo lang::translate('Is Super')?>:</label><br />
<input type="checkbox" name="super" value="1" <?php echo $super_checked; ?>><br />
<input type="submit" name="submit" value="<?php echo lang::translate('Submit')?>">
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
    <td><?php echo lang::translate('Admin')?></td><?php } else { ?>
    <td><?php echo lang::translate('Not Admin')?></td><?php }
        if ($v['verified']) { ?>
    <td><?php echo lang::translate('Verified')?></td><?php } else { ?>
    <td><?php echo lang::translate('Not Verified')?></td><?php } ?>
    <td><?php echo $v['created']?></td>
    <td><?php echo create_link("/account/admin/edit/$v[id]", lang::translate('Edit'))?></td>
    <td><?php echo create_link("/account/admin/delete/$v[id]", lang::translate('Delete'))?></td>
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
<legend><?php echo lang::translate('Delete User')?>:<?php echo $values['username']?></legend>
<p><input type="submit" name="submit" value="<?php echo lang::translate('Delete')?>" /></p>
</fieldset>
</form>
</div>
<?php

}
