account (default account module for CosCMS)
===========================================

### Configuration

All configuration is set in account/account.ini

    ; who are allowed to edit: anon, admin, super, user
    account_allow_edit = "super"
    ; who will see admin link in main navigation: anon, admin, super, user
    account_show_admin_link = "super"
    ; default login url
    account_default_url = "/account/login/index"
    ; facebook secret if using facebook as login method
    account_facebook_api_secret = "secret"
    ; facebook appid if using facebook as login method
    account_facebook_api_appid = "appid"
    ; who can create a user: anon, admin, super, user
    ; if your your system needs users to create accounts - set it to 'anon'
    account_allow_create = "super"
    ; disable editing of accounts - e.g. you don't want anyone to edit accounts
    account_disable_admin_interface = 0
    ; menu item is only in admin items
    account_admin_only = 0
    ; login methods
    account_logins[0] = "email"
    account_logins[1] = "lightopenid"
    ;account_logins[2] = 'facebook'


### Events

You can set events in the account.ini file. The following will call the 
module points with the static method events when account events are triggered: 

account_events[0] = "points::events"

the following actions are possible so far: 

    account_login

This event is fired when a user logs in. 

The following params are sent to the class implementing the event:

    $args = array (
        'action' => 'account_login',
        'user_id' => $account_id,
    );
 
   account_create

this event is fired when an account is created

The following params are sent to the classes implementing the event:

    $args = array (
        'action' => 'create',
        'user_id' => $new_account_id,
    );

### Creating a facebook app 

You will need an facebook app in order to use the facebook login 

Here is a nice tutorial about the process: 

https://developers.facebook.com/docs/beta/opengraph/tutorial/

Create an your app on this page. 

https://developers.facebook.com/apps

Press link on top right corner: 'Create New'

Create settings: 

In settings 'application domain': e.g. example.com
In settings 'Homepage' set e.g: example.com/account/facebook/index
Thinks those two has to correspond.  
