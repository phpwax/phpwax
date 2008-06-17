<?php
/**
 * 	@package PHP-Wax
 */

/**
	*	An class to handle database authentication.
	* Can be extended to check access via a flat file or any other method.
 	* @package PHP-Wax
  */
class WaxAuthDb
{


  /**
	 *	Stores the current user id.
	 *  Used as a test to see if a user is logged in.
	 *	@access protected
	 *	@var int
	 */
 	public $user_field = "username";
 	public $password_field = "password";  
  protected $session_key = "loggedin_user";
 	protected $user_id=null;
  protected $user_object=null;
	protected $db_table = "user";
  protected $encrypt=false;
  
	function __construct($options=array()) {
	  if(isset($options["encrypt"])) $this->encrypt=$options["encrypt"];
	  if(isset($options["db_table"])) $this->db_table=$options["db_table"];
	  if(isset($options["user_field"])) $this->user_field=$options["user_field"];	
	  if(isset($options["password_field"])) $this->password_field=$options["password_field"];		
	  if(isset($options["session_key"])) $this->session_key=$options["session_key"];
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
    return md5($password);
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
      $object = WXInflections::camelize($this->db_table, true);
      $result = new $object($id);
      if($result) {
        $this->user_object = $result;
        $this->user_id = $result->id;
      }
    }
  }

  public function verify($username, $password) {
    $object = WXInflections::camelize($this->db_table, true);
    $user = new $object;
    if($this->encrypt) $password = $this->encrypt($password);
    $result = $user->filter(array($this->user_field=>$username, $this->password_field=>$password))->first();
    if($result->id) {
      $this->user_object = $result;
      $this->user_id = $result->id;
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