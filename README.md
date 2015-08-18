# Account

Account module for CosCMS

# Base options

Base settings:
    
    ; Who can edit accounts
    account_allow_edit = "super"
    ; Default login URL - email
    account_default_url = "/account/login/index"
    ; Redirect on login
    account_redirect_login = "/content/index"
    ; Who can create other users
    account_allow_create = "super"
    ; Types of login - only email here
    account_logins[0] = "email"

# Other login methods

### Enable Google oAUTH login

    account_logins[1] = "google"
    account_google_id = "google id"
    account_google_secret = ""
    account_google_scope = "email"
    account_google_redirect = "http://ebookpublish.org/account/google/redirect"

### Facebook login

    account_logins[2] = "facebook"
    account_facebook_api_secret = ""
    account_facebook_api_appid = "appid"
    account_facebook_scope = 'email'

### Github login

    account_github_id = "app id"
    account_github_secret = ""
    account_github_scope = "user"

# Events

All events exists in `account/config.php`
You can change this file and include your own methods. 
