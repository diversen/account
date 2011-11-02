<?php

template::setTitle(lang::translate('account_facebook_login'));

// check to see if user is allowed to use faccebook login
if (!get_module_ini('account_use_facebook_login')){
    moduleLoader::$status[403] = 1;
    return;
}

//$account = new accountOpenid();
accountFacebook::init();
if (accountFacebook::$loggedIn){
    
    if (@$_SESSION['account_type'] != 'facebook'){
        accountLoginView::logout();
        return;
    }

} 

// Create our Application instance (replace this with your appId and secret).
// Create our Application instance.
$facebook = new Facebook(array(
  'appId'  => get_module_ini('account_facebook_api_appid'),
  'secret' => get_module_ini('account_facebook_api_secret'),
  'cookie' => true,
));

// We may or may not have this data based on a $_GET or $_COOKIE based session.
//
// If we get a session here, it means we found a correctly signed session using
// the Application Secret only Facebook and the Application know. We dont know
// if it is still valid until we make an API call using the session. A session
// can become invalid if it has already expired (should not be getting the
// session back in this case) or if the user logged out of Facebook.
$user = $facebook->getUser();

$me = null;
// Session based API call.
if ($user) {
  try {
    //$uid = $facebook->getUser();
    $me = $facebook->api('/me');

  } catch (FacebookApiException $e) {
    error_log($e);
  }
}

// login or logout url will be needed depending on current user state.
if ($me) {
  // create user in if he does not exists.

  $account = new accountFacebook();
  $row = $account->auth($me['link']);

  if (!$row){
      // we have a facebook session but no user
      $id = $account->createUser($me['link']);
      $_SESSION['id'] = $id;
      $_SESSION['account_type'] = 'facebook';
  } else {
      // we have a row - user exists - we set creds
      $_SESSION['id'] = $row['id'];
      $_SESSION['admin'] = $row['admin'];
      $_SESSION['super'] = $row['super'];
      $_SESSION['account_type'] = 'facebook';
  }
  $logoutUrl = $facebook->getLogoutUrl();
  $uri = uri::getInstance();

  if (isset($_SESSION['redirect_on_login'])){
      $redirect = $_SESSION['redirect_on_login'];
      unset($_SESSION['redirect_on_login']);
      header ("Location: $redirect");
  }

} else {
  session::killSession();
  $loginUrl = $facebook->getLoginUrl(
          
            array(
                'scope'         => 'email,
                    
                    user_birthday,user_location,user_work_history,user_about_me,user_hometown,user_website',

            )
    
          /*offline_access,publish_stream,sms*/
          
          );
  
  /*
   * array(
        'canvas' => 1,
        'fbconnect' => 0,
        'req_perms' => 'publish_stream,offline_access,user_location'
    )
   */
}

// This call will always work since we are fetching public data.
//$naitik = $facebook->api('/dennis.b.iversen');

?>


    <?php if ($me): ?>
    <a href="<?php echo $logoutUrl; ?>">
      <img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif">
    </a>
    <?php else: ?>

    <div>

      <a href="<?php echo $loginUrl; ?>">
        <img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif">
      </a>
    </div>
    <?php endif ?>

    