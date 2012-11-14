<?php

include_once "coslib/date.php";
class accountChangeemail {
    
    /**
     * changes email of an account
     * @param type $user_id the users id
     * @param type $email new email
     */
    function changeEmail ($user_id, $email) {
        $row = dbQ::setSelect('account')->
                filter('id =', $user_id)->
                fetchSingle();
        
        
        if (trim($row['email']) == trim($email)) {
            return true;
        }
        
        
        $values = array ('email' => $email, 'verified' => 0);     
        $res = dbQ::setUpdate('account')->
                setUpdateValues($values)->
                filter('id =', $user_id)->
                exec();

                
        $mysql_date = dateGetDateNow();
        
        // only allow 2 changes per day            
        $bean = RB::getBean('account_email_changes', 'user_id', $user_id);
        
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
        $bean = RB::getBean('account_email_changes', 'user_id', $user_id);
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
        
        $md5 = md5(uniqid(rand(), true));
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
        // compose message from language
        $lang = config::getMainIni('language');
        $message = view::get('account', "lang/$lang/signup_message", $vars);
        
        $from = config::$vars['coscms_main']['site_email'];
        return mail_smtp_html($email, $subject, $message, $from);
    }
    
    /**
     * updates user with values 
     * @param int $user_id
     * @param array $values
     * @return boolean $res true or false
     */
    public function updateUser ($user_id, $values) {
        $res = dbQ::setUpdate('account')->
                setUpdateValues($values)->
                filter('id =', $user_id)->
                exec();
        return $res;
    }
}
