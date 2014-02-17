<?php

class account_api {
    
    /**
     * controler for /account/api/lock/[id]
     * @return void
     */
    public function lockAction () {
        if(session::isSuper()) {
            $id = uri::fragment(3);
            if ($this->lock($id)) {
                echo lang::translate('User with ID <span class="notranslate">{ID}</span> has been locked!', 
                                      array ('ID' => $id)
                );
            }
            
        } else {
            error_module::$message = lang::translate('Not sufficient privileges. Super user is required');
            moduleloader::setStatus(403);
            return;
        }
    }
    
    /**
     * lock an account 
     * @param int $id
     * @return boolean $res true on success else false
     */
    public function lock ($id) {
        $values = array ('locked' => 1);
        return db_q::update('account')->values($values)->filter('id =', $id)->exec();
    }
}

class account_api_module extends account_api {}
