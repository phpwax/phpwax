<?php
/**
	* @package PHP-Wax
  */

/**
 *	Wrapper class for basic session read and writes.
 *	@static
 * @package PHP-Wax
 */
class Session {
		
	private static
  	$ip = null,
		$user_agent = null;

	public static
    $id = false,
    $session_lifetime = 0,
    $session_max_lifetime= "61",
    $session_name= "wxsession",
    $session_no_cookies = 0,
		$user_messages=array();

	static function get($key) {
  	if(self::is_valid_host() && isset($_SESSION[self::get_hash()][$key])) {
    	return $_SESSION[self::get_hash()][$key];
    }
    return null;
  }

  static function set($key, $value) {
  	if(self::is_valid_host()) {
     	$_SESSION[self::get_hash()][$key] = $value;
    }
  }

    static function is_valid_host() {
        if(($_SERVER['REMOTE_ADDR'] == self::$ip) &&
           $_SERVER['HTTP_USER_AGENT'] == self::$user_agent) {
            return true;
        }
        return false;
    }

    static function get_hash() {
      if(!$ua = $_SERVER['HTTP_USER_AGENT']) $ua = "";
      $key = session_id().$ua;
      return md5($key);
    }
    
	static function is_bot() {
    $isBot=false;
	  $ua=$_SERVER['HTTP_USER_AGENT'];
    $bots=array('googlebot','ask jeeves','slurp','fast','scooter','zyborg','msnbot');
    foreach($bots as $bot) {
			if(stristr($ua, $bot)) { return true; }
		}
	  return false;
  }

    static function start() {
			if(!self::$id){
        ini_set('session.name', self::$session_name);
        ini_set('session.cookie_lifetime', self::$session_lifetime);
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_maxlifetime', self::$session_max_lifetime * 60);
        ini_set('session.use_trans_sid', self::$session_no_cookies);
        ini_set('arg_separator.output', "/");

        header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');

        self::$ip = $_SERVER['REMOTE_ADDR'];
        self::$user_agent = $_SERVER['HTTP_USER_AGENT'];

		# Don't start a session if this is a search engine
        if(self::is_bot()) { return false; }

        if(self::is_valid_host() && $_REQUEST[self::$session_name]) {
            session_id($_REQUEST[self::$session_name]);
        }

        session_cache_limiter("must-revalidate");
        session_start();
        self::$id = session_id();
			}
    }

    static function destroy_session() {
        session_destroy();
    }

    static function unset_session() {
        session_unset($_SESSION[self::get_hash()]);
    }

    static function unset_var($key) {
        if(self::is_valid_host() && isset($_SESSION[self::get_hash()][$key])) {
            unset($_SESSION[self::get_hash()][$key]);
        }
    }

    static function isset_var($key) {
        if(self::is_valid_host()) {
            if(isset( $_SESSION[self::get_hash()][$key] )) {
                return true;
            }
        }
        return false;
    }
		
		static function add_message($string) {
			$existing=self::get('user_messages');
			$existing[]=$string;
			self::set('user_messages', $existing);
			return true;
		}
		
		static function add_error($string) {		
			$existing=self::get('user_errors');
			$existing[]=$string;
			self::set('user_errors', $existing);
			return true;
		}

}

?>
