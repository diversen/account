<?php
include_module('account/facebook');

class account_facebook_publish extends account_facebook {
    public function getPublishAuth () {
        $this->login('publish_actions');
    }
    
    public function indexAction () {
        $p = new account_facebook_publish();
        $this->getPublishAuth();
    } 
    
    public function testAction () {
        $facebook = $this->getFBObject();
        print_r($facebook);
        $user = $facebook->getUser();
        print_r($user);
    }
}