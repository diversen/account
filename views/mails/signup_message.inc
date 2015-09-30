<?php

use diversen\lang;

?>
<?=lang::translate('Hi')?>!

<?=lang::translate('You have connected with {SITE_NAME} and requested an account', array ('SITE_NAME' => $vars['site_name']))?>. 
        
<?=lang::translate('In order to activate your account, you just have to press the following link or copy and paste into your browsers URL line')?>. 

<?=$vars['verify_key']?> 

<?=lang::translate('Kind Regards')?> <?=$vars['site_name']?>
