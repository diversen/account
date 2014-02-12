<?php

class account_api {
    
    public function lockAction () {
        if(session::isSuper()) {
            $id = uri::fragment(3);
            $values = array ('locked' => 1);
            $res = db_q::update('account')->values($values)->filter('id =', $id)->exec();
            if ($res) {
                echo lang::translate('User with ID <span class="notranslate">{ID}</span> has been locked!', 
                        array ('ID' => $id)
                );
            }
        } else {
            user::lockedSet403(lang::translate('Not sufficient privileges. Super user is required'));
        }
    }
}
