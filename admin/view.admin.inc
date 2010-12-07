<?php



/**
 * view file for account/admin
 * @package    account
 */

/**
 * include captcha lib.
 */
include_once "lib/captcha.php";

class viewAccountAdmin {

   /**
    * function for updating a user
    *
    * @param array row to use when updating a user
    */
    function updateEmailUser ($values){
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
    <label for="username"><?php echo lang::translate('Username')?>:</label><br />
    <input type="text" name="username" value="<?php echo htmlspecialchars($values['username'])?>"><br />
    <label for="email"><?php echo lang::translate('Email')?>:</label><br />
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

    function updateUrlUser ($values){
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
    <label for="url"><?php echo lang::translate('account_urlid')?></label> ::
    <?php echo htmlspecialchars($values['url'])?><br />
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
    function users($users){ ?>
    <!-- view_users -->
    <div id="account_list">
    <table><?php
        foreach ($users as $k => $v) {

            if (!empty($v['url'])){
                self::viewUrlUser($v);
            } else {
                self::viewEmailUser($v);
            }

     } ?>
    </table>
    </div>
    <!-- / view_users -->
    <?php

    }


    public static function viewEmailUser ($v) { ?>
<!-- email user -->
<tr>
    <td><?=$v['username']?></td>
    <td><?=$v['email']?></td><?

    if ($v['admin']) { ?>
    <td><?=lang::translate('Admin')?></td><? } else { ?>
    <td><?=lang::translate('Not Admin')?></td><? }

    if ($v['super']) { ?>
    <td><?=lang::translate('Super')?></td><? } else { ?>
    <td><?=lang::translate('Not Super')?></td><? }

    if ($v['verified']) { ?>
    <td><?=lang::translate('Verified')?></td><? } else { ?>
    <td><?=lang::translate('Not Verified')?></td><? } ?>
    <td><?=$v['created']?></td>
    <td><?=create_link("/account/admin/edit/$v[id]", lang::translate('Edit'))?></td>
    <td><?=create_link("/account/admin/delete/$v[id]", lang::translate('Delete'))?></td>
</tr><?php
    }

    public static function viewUrlUser ($v) { ?>
<!-- email user -->
<tr>
    <td><?=$v['username']?></td>
    <td>&nbsp;</td><?

    if ($v['admin']) { ?>
    <td><?=lang::translate('Admin')?></td><? } else { ?>
    <td><?=lang::translate('Not Admin')?></td><? }

    if ($v['super']) { ?>
    <td><?=lang::translate('Super')?></td><? } else { ?>
    <td><?=lang::translate('Not Super')?></td><? }

    if ($v['verified']) { ?>
    <td>&nbsp;</td><? } else { ?>
    <td>&nbsp;</td><? } ?>
    <td><?=$v['created']?></td>
    <td><?=create_link("/account/admin/edit/$v[id]", lang::translate('Edit'))?></td>
    <td><?=create_link("/account/admin/delete/$v[id]", lang::translate('Delete'))?></td>
</tr><?php
    }
    
    
    /**
     * function for view a delete user form
     *
     * @param array row with user
     */
    function delete ($values){ ?>
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
}