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
        //print_r($user);
    }
    
    public function postAction () {
        $facebook = $this->getFBObject();
        $user = $facebook->getUser();
        $result = $facebook->api("/$user/feed", 'POST', array('message' => "http://en.wikipedia.org/wiki/Infinite_monkey_theorem"));
        var_dump($result);
    }
}