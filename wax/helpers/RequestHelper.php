<?php

class RequestHelper extends WXHelpers {
  
  
  public function get($name) {
    return Request::get($name);
  }
  
  public function post($name) {
    return Request::post($name);
  }
   
  
  
}