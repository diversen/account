<?php

use diversen\lang;

?>
<?=lang::translate('Hi')?>!

<?=lang::translate('You have connected with {SITE_NAME} because you have lost your password', array ('SITE_NAME' => $vars['site_name'] ))?> 
        
<?=lang::translate('In order to create a new password just press the following link or paste it into your browsers URL line')?>.

<?=$vars['verify_key']?>

<?=lang::translate('If you have not requested a new password then you can just delete this message')?>.

<?=lang::translate('Kind Regards')?> <?=$vars['site_name']?>