<?php

//if sessions are used, add http headers defining the cookie use. this is above the class definition since it only needs to be sent once for all sessions, not once per session.
if(class_exists("WaxEvent", false)){
  WaxEvent::add("wax.post_render", function(){
    $response = WaxEvent::data();
    $response->add_header('P3P', ' CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
    
    //garbage collection
    $cmd = "php ".__FILE__." ";
    $run_garbage_collection = true;
    foreach(WaxSession::$garbage_collection_folders as $dir){
      if(($stats = stat($dir."/garbage.collect.lock")) && time() < $stats[9]){
        $run_garbage_collection = false;
        break;
      }
      $cmd .= $dir." ";
    }
    if($run_garbage_collection) exec($cmd." > /dev/null &");
  });
}

class WaxSession {
  public static
    $data = array(),
    $updated = array(),
    $garbage_collection_folders = array(),
    $garbage_collection_timeout = 1440;
  
  public
    $bots = array('googlebot','ask jeeves','slurp','fast','scooter','zyborg','msnbot'),
    $file_storage_prefix = SESSION_DIR,
    $id = false,
    $lifetime = 0,
    $name = "wxsession";
  
  function __construct($data = array()){
    if($this->is_bot()) return; // Don't start a session if this is a search engine
    
    //set passed in data
    foreach($data as $k => $v) $this->$k = $v;
    
    //get id from request or generate an id
    if(($this->id = $_COOKIE[$this->name]) || ($this->id = Request::param($this->name))){
      //initialize data from cross-request storage
      if(!static::$data[$this->name] && ($stats = stat($this->file_storage()))){
        if(time() < $stats[9]) static::$data[$this->name] = unserialize(file_get_contents($this->file_storage()));
        else unlink($this->file_storage());
      }
    }else $this->id = $this->safe_encrypt($_SERVER['REMOTE_ADDR'].microtime().mt_rand());
    
    //add folder to collection of folders to be garbage collected
    static::$garbage_collection_folders[] = $this->file_storage_dir();
    
    //set cookie on render to propogate session
    $session = $this;
    WaxEvent::add("wax.post_render", function() use ($session) {
      $response = WaxEvent::data();
      $response->set_cookie($session->name, $session->id, $session->lifetime?(time() + $session->lifetime):false);
    });
  }
  
  /**
   * write session data back to session storage on destruction
   */
  function __destruct(){
    if(!is_dir($this->file_storage_dir())){
      if(!mkdir($this->file_storage_dir(), 0750, true)) throw new WaxException("Session not writable - ".$this->file_storage_dir());
    }
    if(static::$updated[$this->name]){
      if(file_put_contents($this->file_storage(), serialize(static::$data[$this->name])) === false) throw new WaxException("Session not writable - ".$this->file_storage_dir());
    }
    if(static::$updated[$this->name] || !$this->lifetime) //write browser close sessions on read, to update their time on every read
      touch($this->file_storage(), time() + ($this->lifetime?$this->lifetime:(WaxSession::$garbage_collection_timeout * 10)));
  }
  
  public function get($key){
    if(!$key) return static::$data[$this->name];
    else return static::$data[$this->name][$key];
  }
  
  public function set($key, $value) { static::$updated[$this->name] = true; static::$data[$this->name][$key] = $value; }
  
  public function unset_var($key) { static::$updated[$this->name] = true; unset(static::$data[$this->name][$key]); }
  
  public function get_hash() { return $this->id; }
  
  public function isset_var($key) { return isset(static::$data[$this->name][$key]); }
  
  public function add_message($string) { static::$updated[$this->name] = true; static::$data[$this->name]['user_messages'][] = $string; }
  
  public function add_error($string) { static::$updated[$this->name] = true; static::$data[$this->name]['user_errors'][] = $string; }
  
  public function unset_session() { static::$updated[$this->name] = true; unset(static::$data[$this->name]); }
  
  public function destroy_session() { $this->unset_session(); }
  
  public function is_bot() {
    foreach($this->bots as $bot)
      if(stristr($_SERVER['HTTP_USER_AGENT'], $bot))
        return true;
  }
  
  public function file_storage_dir(){ return "$this->file_storage_prefix$this->name"; }
  
  public function file_storage(){ return $this->file_storage_dir()."/".$this->id; }
  
  protected function safe_encrypt($password, $iterations = 8) {
    $itoa64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789./';
    $random = substr(md5(mt_rand()), 0, 16);
    if($iterations < 4 || $iterations > 31) $iterations = 8;
    $salt = '$2a$';
    $salt .= chr(ord('0') + $iterations / 10);
    $salt .= chr(ord('0') + $iterations % 10);
    $salt .= '$';
    $i = 0;
    do {
      $c1 = ord($random[$i++]);
      $salt .= $itoa64[$c1 >> 2];
      $c1 = ($c1 & 0x03) << 4;
      if ($i >= 16) {
        $salt .= $itoa64[$c1];
        $hash = crypt($password, $salt);
        return strlen($hash) == 60 ? str_replace("/", "_", $hash) : '*';
      }
      $c2 = ord($random[$i++]);
      $c1 |= $c2 >> 4;
      $salt .= $itoa64[$c1];
      $c1 = ($c2 & 0x0f) << 2;
      $c2 = ord($random[$i++]);
      $c1 |= $c2 >> 6;
      $salt .= $itoa64[$c1];
      $salt .= $itoa64[$c2 & 0x3f];
    } while (true);
  }
  
  /**
   * depreciated. start does nothing now, since starting happens in construction. making use of sessions in any way will start them behind the scenes.
   */
  public function start(){}
}

//when run from console directly, garbage collect. parameters are directories to be processed
if($argv){
  array_shift($argv);
  foreach($argv as $dir){
    if(!is_dir($dir)) continue;
    touch("$dir/garbage.collect.lock", time() + WaxSession::$garbage_collection_timeout);
    foreach(glob("$dir/*") as $file){
      if($file == "$dir/garbage.collect.lock") continue;
      if(($stats = stat($file)) && time() > $stats[9])
        unlink($file);
    }
  }
}

?>
