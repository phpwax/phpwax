<?php
/**
 * 
 *
 * @author Ross Riley
 * @package PHP-Wax
 **/

/**
 * Reusable HTTP-Response class
 *
 * @package PHP-Wax
 * @author Ross Riley
 * 
 **/
class WaxResponse {

  public $headers=array();
  public $body;
  public $status="200";
  public $status_map = array(
    "200"=>"HTTP/1.1 200 OK",
    "301"=>"HTTP/1.1 301 Moved Permanently",
    "302"=>"HTTP/1.1 302 Found",
    "404"=>"HTTP/1.1 404 Not Found"
  );
  
  
  public function write($content){
    $this->body.=$content;
  }
  
  public function redirect($url, $status="302") {
    $this->status = $status;
    $this->headers["Location"] = $url;
  }
  
  public function permanent_redirect($url, $status="301") {
    $this->status = $status;
    $this->headers["Location"] = $url;
  }
  
  public function headers() {return $this->headers;}
  
  public function add_header($name, $value) {
    $this->headers[$name]=$value;
  }
  
  public function remove_header($name) {
    unset($this->headers[$name]);
  }
    
  public function body() {return $this->body;}
  
  public function set_cookie($key, $value='', $expires=false, $path=false, $domain=false, $secure=false) {
    $cookie[] = "$key=$value";
    if($expires) $cookie[] = "Expires=".date("D, d-M-y H:i:s T", is_numeric($expires)?$expires:strtotime($expires));
    if($path) $cookie[] = "Path=$path";
    else $cookie[] = "Path=/";
    if($domain) $cookie[] = "Domain=$domain";
    if($secure) $cookie[] = "Secure";
    
    if(!$this->headers["Set-Cookie"]) $this->headers["Set-Cookie"] = array();
    $this->headers["Set-Cookie"][] = implode(";", $cookie);
  }
  
  public function delete_cookie($key, $path='/', $domain=false) {
    if(!$this->headers["Set-Cookie"]) $this->headers["Set-Cookie"] = array();
    $this->headers["Set-Cookie"][] = "$key='';path=$path;domain=$domain;expires= Thu, 01-Jan-1970 00:00:00 GMT";
  }
  
  public function execute() {
    header($this->status_map[$this->status]);
    header("X-Info: Powered By PHP-Wax");
    foreach($this->headers as $header=>$val)
      if(is_array($val))
        foreach($val as $v)
          header($header.":".$v, false);
      else
        header($header.":".$val, false);
    echo $this->body();
    WaxEvent::run("wax.end");
  }
  	
}

