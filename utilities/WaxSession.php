<?php
ini_set('session.use_trans_sid', 0);
session_cache_limiter("must-revalidate");
if(!headers_sent()) header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
class WaxSession {
  public
    $bots = array('googlebot','ask jeeves','slurp','fast','scooter','zyborg','msnbot'),
    $id = false,
    $session_lifetime = 0,
    $session_name = "wxsession",
    $data = false,
    $hash = false;
  
  function __construct($data = array()){
    if($this->is_bot()) return; // Don't start a session if this is a search engine
    
    //compute hash
    if(!$ua = $_SERVER['HTTP_USER_AGENT']) $ua = "";
    $key = session_id().$ua;
    $this->hash = md5($key);
    
    //set passed in data
    foreach($data as $k => $v) $this->$k = $v;
    
    //start session and load up data
    $this->start();
    $this->data = $_SESSION[$this->get_hash()];
  }
  
  /**
   * write session data back to session storage on destruction
   */
  function __destruct(){
    $this->start();
    $_SESSION[$this->get_hash()] = $this->data;
  }
  
  public function start() {
    session_set_cookie_params($this->session_lifetime);
    if($_REQUEST[$this->session_name]) session_id($_REQUEST[$this->session_name]);
    session_name($this->session_name);
    session_start();
    $this->id = session_id();
  }
  
  public function get($key) { return $this->data[$key]; }
  
  public function set($key, $value) { $this->data[$key] = $value; }
  
  public function unset_var($key) { unset($this->data[$key]); }
  
  public function get_hash() { return $this->hash; }
  
  public function isset_var($key) { return isset($this->data[$key]); }
  
  public function add_message($string) { $this->data['user_messages'][] = $string; }
  
  public function add_error($string) { $this->data['user_errors'][] = $string; }
  
  public function is_bot() {
    foreach($this->bots as $bot)
      if(stristr($_SERVER['HTTP_USER_AGENT'], $bot))
        return true;
  }
  
  public function destroy_session() {
    session_name($this->session_name);
    session_destroy();
  }
  
  public function unset_session() {
    $this->data = array();
  }
  
}
?>
