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
  
  public function redirect($url) {
    $this->status = "302";
    $this->headers["Location"] = $url;
  }
  
  public function permanent_redirect($url) {
    $this->status = "302";
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
  
  public function set_cookie($key, $value='', $max_age=false, $expires=false, $path='/', $domain=false, $secure=false) {
    $this->headers["Set-Cookie"]="$key=$value;max_age=$max_age;expires=$expires;path=$path;domain=$domain;secure=$secure";
  }
  
  public function delete_cookie($key, $path='/', $domain=false) {
    $this->headers["Set-Cookie"] = "$key='';path=$path;domain=$domain;expires= Thu, 01-Jan-1970 00:00:00 GMT";
  }
  
  public function execute() {
    header($this->status_map[$this->status]);
    header("X-Info: Powered By PHP-Wax");
    foreach($this->headers as $header=>$val) {
      header($header.":".$val);
    }
    echo $this->body();
    WaxEvent::run("wax.end");
    exit;
  }
  	
}

