<?php

use diversen\moduleloader;

moduleloader::includeTemplateCommon('openid-selector');
openid_selector_load_assets();
echo openid_selector_get_form();
