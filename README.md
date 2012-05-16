account (default account module for CosCMS)
===========================================

### Events

You can set events in the account.ini file: 

account_events[0] = "points::events"

the following actions are possible so far: 

#### `account_login`:

The following params are called to the class implementing the event:

    $args = array (
        'action' => 'account_login',
        'user_id' => $account['id'],
    );

#### `account_create`:

The following params are called to the class implementing the event:

    $args = array (
        'action' => 'account_create',
        'user_id' => $new,
    );

### Facebook login 

https://developers.facebook.com/docs/beta/opengraph/tutorial/

Create an app on this page. 

https://developers.facebook.com/apps

Press link on top right corner: 'Create New'

Create settings: 

In settings 'application domain': e.g. example.com
In settings 'Homepage' set e.g: example.com/account/facebook/index
Thinks those two has to correspond.  

