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
        $user = $facebook->getUser();
        print_r($user);
    }
    
    public function postAction () {
        $facebook = $this->getFBObject();
        $user = $facebook->getUser();
        $share = $this->getShare();
        $result = $facebook->api("/$user/feed", 'POST', array('message' => $share));
        var_dump($result);
    }
    
    public function autoAction () {
        $facebook = $this->getFBObject();
        $share = $this->getShare();
        $user_id = "100003840996213";
        $result = $facebook->api("/$user_id/feed", 'POST', array('message' => $share));
        print_r($result);
    }
    
    public function getShare () {
        return "http://www.youtube.com/watch?v=amLungGziP0&list=FLxDWzD2t4uhiZkvmXP6W2SA";
    }
}