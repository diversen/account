<!--
<form action="?login=https://www.google.com/accounts/o8/id" method="post">
<button>Login with Google</button>
</form>

<form action="?login=https://me.yahoo.com" method="post">
<button>Login with Yahoo</button>
</form>

<form action="?login=https://iversen.myopenid.com" method="post">
<button>Login with Yahoo</button>
</form>-->

<?php

include_template_inc('openid-selector');
openid_selector_load_assets();
echo openid_selector_get_form();