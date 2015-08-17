<?php

namespace modules\account;

class events {
    
    /**
     * Event after a user has been created in 'account' table
     * @param int $last_insert_id
     */
    public static function createDbUser ($last_insert_id) {
        
    }
    
    /**
     * Event after user is set as verified in 'account' table
     * @param int $user_id
     */
    public static function verifyUpdateDb($user_id) {
        
    }
    
}