<?php
/**
	* @package wx.php.core
  */

/**
 *	Wrapper class for basic session read and writes.
 *	@static
 * @package wx.php.core
 */
class Session {
		
	private static
  	$ip = null,
		$user_agent = null;

	public static
    $id = null,
    $session_lifetime = 0,
    $session_max_lifetime= "61",
    $session_name= "Session_ID",
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
        if(($_SERVER['REMOTE_ADDR'] == self::$ip || self::is_aol_host()) &&
           $_SERVER['HTTP_USER_AGENT'] == self::$user_agent) {
            return true;
        }
        return false;
    }

    static function is_aol_host() {
        if(ereg("proxy\.aol\.com$", gethostbyaddr($_SERVER['REMOTE_ADDR'])) ||
           stristr($_SERVER['HTTP_USER_AGENT'], "AOL")) {
            return true;
        }
        return false;
    }

    static function get_hash() {
        $key = session_id().$_SERVER['HTTP_USER_AGENT'];
        if(!self::is_aol_host()) {
            $key .= $_SERVER['REMOTE_ADDR'];
        }
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
        # set the session default for this app
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

        //session_cache_limiter("must-revalidate");
        session_start();
        self::$id = session_id();
    }

    static function destory_session() {
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
		

}

?>
