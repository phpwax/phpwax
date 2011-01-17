<?php
/**
 * 	@package PHP-Wax
 */

/**
  * An class to handle database authentication.
  * Can be extended to check access via a flat file or any other method.
  * @package PHP-Wax
  */
class WaxAuthDb {


  /**
   *  Stores the current user id.
   *  Used as a test to see if a user is logged in.
   *  @access protected
   *  @var int
   */
  public $user_field = "username";
  public $password_field = "password";  
  protected $session_key = "loggedin_user";
  protected $user_id=null;
  protected $user_object=null;
  protected $db_table = "user";
  protected $user_class = false;
  protected $encrypt=false;
  protected $bcrypt=false;
  protected $salt=false;
  protected $algorithm = "md5";

  function __construct($options=array()) {
    if(is_string($options["encrypt"]) || is_numeric($options["encrypt"])){
      $this->encrypt = true;
      if($options['salt']) $this->salt = $options["salt"];
    }elseif(isset($options["encrypt"])) $this->encrypt=$options["encrypt"];
    if(isset($options["bcrypt"])) $this->bcrypt=TRUE;

    if(isset($options["db_table"])) $this->db_table=$options["db_table"];
    if(isset($options["user_class"])) $this->user_class=$options["user_class"];
    if(isset($options["salt"])) $this->salt=$options["salt"];
    if(isset($options["user_field"])) $this->user_field=$options["user_field"];	
    if(isset($options["password_field"])) $this->password_field=$options["password_field"];		
    if(isset($options["session_key"])) $this->session_key=$options["session_key"];
    if(isset($options["algorithm"])) $this->algorithm=$options["algorithm"];
    $this->setup_user();
  }
	
	/**
	 *	Sees if a loggedin_user is set in the session.
	 *	@access public
	 *	@return bool
	 */ 
  public function check_logged_in() { 
		if($this->user_id) {
		  return true;
		}
		return false;
  }
  
  public function is_logged_in() {
    return $this->check_logged_in();
  }
  
  public function get_user() {
    return $this->user_object;
  }

  
  protected function encrypt($password) {
		if($this->algorithm == "md5" && !$this->salt) return md5($password);
    else return hash_hmac($this->algorithm, $password, $this->salt);
  }


	/**
		* Much safer bcrypt implementation for PHP 5.3+ 
		* Use this wherever possible, adjust iterations for extra security
	 	*
	 	* @return $hash 
	 	**/

	protected function safe_encrypt($password, $iterations = 8) {
		$random = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
	  $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	  if ($iterations < 4 || $iterations > 31) $iterations = 8;
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
	          return strlen($hash) == 60 ? $hash : '*';
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
		* Corresponding hash check of safe_encrypt method
	 	*
	 	* @return boolean 
	 	**/
	function safe_check($password, $hash) {
		return crypt($password, $hash) == $hash;
	}

	/**
	 *	Logs out a user by unsetting loggedin_user variable in the session.
	 *	@static
	 *	@access public
	 *	@return bool
	 */
  public function logout() {
		$this->user_id = null;
		$this->user_object=null;
		return true;
  }

	/**
	 *	Makes a random password of specified length.
	 *	@access protected
	 *	@return string
	 *	@param int $length
	 */	
	protected function makeRandomPassword($length) { 
	 	$salt = "zqrstuvwxypnmkjhgfehc56789ab43210ZYXWVHJKLMNPRSTUGFEDCBA"; 
		srand((double)microtime()*1000000); 
  	for($i = 0;$i < $length;$i++) { 
  	  $num = rand() % 59; 
  		$tmp = substr($salt, $num, 1); 
  		$pass = $pass . $tmp; 
  	} 
	  return $pass; 
	}

  public function setup_user() {
    if($id = Session::get($this->session_key)) {
      if($this->user_class) $object = $this->user_class; 
      else $object = Inflections::camelize($this->db_table, true);
      $result = new $object($id);
      if($result->primval) {
        $this->user_object = $result;
        $this->user_id = $result->primval;
        return true;
      }else return false;
    }
  }

  public function verify($username, $password) {
    $object = Inflections::camelize($this->db_table, true);
    $user = new $object;
    if($this->bcrypt) {
			$result = $user->filter(array($this->user_field=>$username))->first();
			if($result->primval && $this->safe_check($result->{$this->password_field})) return TRUE;
			else return FALSE;
		}
    if($this->encrypt) $password = $this->encrypt($password);
    $result = $user->filter(array($this->user_field=>$username, $this->password_field=>$password))->first();
    if($result->primval) {
      $this->user_object = $result;
      $this->user_id = $result->primval;
      return true;
    }
    return false;
  }
  
  function __destruct() {
    if($this->user_id) {
      Session::set($this->session_key, $this->user_id);
    } else {
      Session::unset_var($this->session_key);
    }
  }
	
}
?>