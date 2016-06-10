<?php

use diversen\lang;

?>
<?=lang::translate('Hi')?>!

<?=lang::translate("A colleague or friend has asked you to collaborate on the site on {SITE_NAME}.", array ('SITE_NAME' => $vars['site_name'] ))?> 

<?=lang::translate('If you do not want to collaborate on the site, then you can just delete this message')?>. 

<?=lang::translate('Kind Regards')?> <?=$vars['site_name']?>