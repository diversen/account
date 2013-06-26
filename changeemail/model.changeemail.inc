<?php

/**
 * contains class for changing users email
 * @package account
 */
include_once "coslib/date.php";

/**
 * contians class for changing users email
 * @package account
 */
class accountChangeemail {
    
    public $errors = array ();
    
    /**
     * checks is user_id can change his email
     * examines if the email is the same as his accounts email
     * then it examines if the email is used by another user
     * @param  int $user_id
     * @param string $email
     * @return int $res
     *                  -1 if email is the same as users existing email
     *                   0 if email exists for another account
     *                   1 Ok to shift email
     */
    public function changeEmailPossible ($user_id, $email) {
        $email = strings_mb::tolower($email);
        $row = user::getAccount($user_id);
        
        
        // if mails are identical - we can change to the same email.  
        if (trim($row['email']) == trim($email)) {
            return -1;
        }
        
        $account = new account();
        $user = $account->getUserFromEmail($email, null, true);
        if (!empty($user) && ($user['id'] != $user_id) ) {
            $this->errors['email_exists'] = lang::translate('account: error: email exists');
            return 0;
        }
        
        return 1;
    }
    
    /**
     * changes email of an account
     * @param type $user_id the users id
     * @param type $email new email
     */
    public function changeEmail ($user_id, $email) {

        
        $pos = $this->changeEmailPossible($user_id, $email);
        if ($pos == -1) {
            return true;
        }
        if ($pos == 0) {
            return false;
        }
       
        $values = array ('email' => $email, 'verified' => 0);     
        $res = db_q::update('account')->
                values($values)->
                filter('id =', $user_id)->
                exec();
                
        $mysql_date = dateGetDateNow();
        
        // only allow 2 changes per day
        $bean = cosRB::getBean('account_email_changes', 'user_id', $user_id);
        
        $email_max_changes = 1000;
        if ($bean->id) {
            if ($bean->date_try == $mysql_date) {
                if ($bean->tries >= $email_max_changes) {
                    return false;
                }
            } else {
                R::trash($bean);  
            }            
        }
        
        $bean = cosRB::getBean('account_email_changes', 'user_id', $user_id);
        $bean->user_id = $user_id;
        $bean->tries++;
        $bean->date_try = $mysql_date;
        R::store($bean);
        
        $this->sendVerifyMail($email, $user_id);
        
        $events = config::getModuleIni('account_events');
        
        if ($res) {
           $params = array (
                            'action' => 'account_change_email',
                            'user_id' => $user_id,
                            'email' => $email);
            
             event::getTriggerEvent(
                     $events, 
                        $params);
        }
        return true;
    }
    
    
    /**
     * send a verify email
     * @param type $email
     * @param type $user_id
     * @param type $md5
     * @return boolean $res 
     */
    public function sendVerifyMail ($email, $user_id) {
        
        $md5 = random::md5();
        $values = array ('md5_key' => $md5, 'email' => $email);       
        $res = $this->updateUser($user_id, $values);
        if (!$res) {
            return false;
        }
        
        $subject = lang::translate('account_signup_subject');
        
        $scheme = config::getHttpScheme();
        $vars['site_name'] = "$scheme://$_SERVER[HTTP_HOST]";
        $subject.= " " . $vars['site_name'];
        $vars['verify_key'] = "$vars[site_name]/account/create/verify/$user_id/$md5";
        $vars['user_id'] = $user_id;

        $lang = config::getMainIni('language');
        $message = array ();
        
        $message['txt'] = view::get('account', "lang/$lang/signup_message", $vars);
        $message['html'] = view::get('account', "lang/$lang/signup_message_html", $vars);
        
        $from = config::$vars['coscms_main']['site_email'];
        return cosMail::multipart($email, $subject, $message, $from);
    }
    
    /**
     * updates user with values 
     * @param int $user_id
     * @param array $values
     * @return boolean $res true or false
     */
    public function updateUser ($user_id, $values) {
        $res = db_q::update('account')->
                values($values)->
                filter('id =', $user_id)->
                exec();
        return $res;
    }
}
