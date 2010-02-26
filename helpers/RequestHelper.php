<?php

class RequestHelper extends WXHelpers {
  
  
  public function get($name, $clean=false) {
    return Request::get($name, $clean);
  }
  
  public function post($name, $clean=false) {
    return Request::post($name, $clean);
  }
  
  public function param($name) {
    return Request::param($name);
  }
  
  public function filter($name) {
    return Request::filter(Request::param($name));
  }
  
  
  
  
}