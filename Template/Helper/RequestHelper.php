<?php
namespace Wax\Template\Helper;


class RequestHelper extends Helper {
  
  
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
  
  public function url($options) {
    return WaxUrl::build_url($options);
  }
  
  
}

Wax::register_helper_methods("RequestHelper", array("get","post","param","filter","url"));
