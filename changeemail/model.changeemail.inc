<?php

//include_module('account')

class accountChangeemail {
    
    /**
     * changes email of an account
     * @param type $user_id the users id
     * @param type $email new email
     */
    function changeEmail ($user_id, $email) {
        
        $values = array ('email' => $email, 'verified' => 0);     
        $res = dbQ::setUpdate('account')->
                setUpdateValues($values)->
                filter('id =', $user_id)->
                exec();

        $events = config::getModuleIni('account_events');
        
        if ($res) {
            //print_r($events); die;
           $params = array (
                            'action' => 'account_change_email',
                            'user_id' => $user_id,
                            'email' => $email);
             //print_r($params); die; 
            
             event::getTriggerEvent(
                     $events, 
                        $params);
        }
    }
    
}