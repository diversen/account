<?php

function account_facebook_login () {

    // check to see if user is allowed to use faccebook login
    $account_logins = config::getModuleIni('account_logins');
    if (!in_array('facebook', $account_logins)){
        moduleLoader::setStatus(403);
        return;
    }

    //$account = new accountOpenid();
    accountFacebook::init();
    if (accountFacebook::$loggedIn){

        if ($_SESSION['account_type'] != 'facebook'){
            accountLoginView::logout();
            return;
        }

    } 

    // Create our Application instance (replace this with your appId and secret).
    // Create our Application instance.
    $facebook = new Facebook(array(
        'appId'  => config::getModuleIni('account_facebook_api_appid'),
        'secret' => config::getModuleIni('account_facebook_api_secret'),
        /*'cookie' => true, */ 
    ));

    // We may or may not have this data based on a $_GET or $_COOKIE based session.
    //
    // If we get a session here, it means we found a correctly signed session using
    // the Application Secret only Facebook and the Application know. We dont know
    // if it is still valid until we make an API call using the session. A session
    // can become invalid if it has already expired (should not be getting the
    // session back in this case) or if the user logged out of Facebook.
    $user = $facebook->getUser();

    if ($user) {
        try {
            // Proceed knowing you have a logged in user who's authenticated.
            $user_profile = $facebook->api('/me');
            $logoutUrl = $facebook->getLogoutUrl(
                array ('next' => 
                    accountFacebook::getLogoutNext()
                    )
            );
        } catch (FacebookApiException $e) {
            cos_debug($e->message);
        }
    } else {
        $user_profile = null;
    }

    // login or logout url will be needed depending on current user state.
    if ($user_profile) {
        $account = new accountFacebook();
        $row = $account->auth($user_profile['link']);

        if (empty($row)){
            
            // we have a facebook session but no user in database
            $id = $account->createUser($user_profile);
            $_SESSION['id'] = $id;
            $_SESSION['account_type'] = 'facebook';

            $args = array (
                'action' => 'account_create',
                'user_id' => $id,
            );

            event::getTriggerEvent(
                config::getModuleIni('account_events'), 
                $args
            );

            $args = array (
                'action' => 'account_login',
                'user_id' => $id,
            );

            event::getTriggerEvent(
                config::getModuleIni('account_events'), 
                $args
            );


        } else {
            // we have a row - user exists - we set creds
            $_SESSION['id'] = $row['id'];
            $_SESSION['admin'] = $row['admin'];
            $_SESSION['super'] = $row['super'];
            $_SESSION['account_type'] = 'facebook';

            $args = array (
                'action' => 'account_login',
                'user_id' => $row['id'],
            );

            event::getTriggerEvent(
                config::getModuleIni('account_events'), 
                $args
            );
        }
        
        // check for redirect on login
        if (isset($_SESSION['redirect_on_login'])){
            $redirect = $_SESSION['redirect_on_login'];
            unset($_SESSION['redirect_on_login']);
            header ("Location: $redirect");
        }

    } else {
        session::killSession();
        $scope = facebook_get_scope();
        $loginUrl = $facebook->getLoginUrl(
                array(
                    'scope' => $scope,
                    'dialog' => 'popup'
                )
            );
    }



    // display login or logout

    if ($user) { ?>
    <a href="<?php echo $logoutUrl; ?>">Log Out
      <!--<img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif">-->
    </a>
    <?php } else { ?>

    <div>

      <a href="<?php echo $loginUrl; ?>">Log in
        <!--<img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif">-->
      </a>
    </div>
    <?php } 
    
}

function facebook_get_login_url ($options = array ()) {

    $facebook = new Facebook(array(
        'appId'  => config::getModuleIni('account_facebook_api_appid'),
        'secret' => config::getModuleIni('account_facebook_api_secret'),
        /*'cookie' => true, */ 
    ));
    
    $scope = facebook_get_scope();
    $ary = 
        array(
            'scope' => $scope,
           // 'dialog' => 'popup'
        );
    $ary+=$options;
   // print_r($ary); die;
    
    
    return $loginUrl = $facebook->getLoginUrl(
        
    );
}

function facebook_get_scope () {
    $scope = 'email,';
    $scope.= 'user_birthday,';
    $scope.= 'user_location,';
    $scope.= 'user_work_history,';
    $scope.= 'user_about_me,';
    $scope.= 'user_hometown,';
    $scope.= 'user_website';
    return $scope;
}
