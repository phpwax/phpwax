<?php
/**
 * 	@package php-wax
 */

/**
	*	An abstract base class to handle basic authentication.
	* Can be extended to check access via a db or flat file.
 	* @package php-wax
  */
class WXAuthenticate
{


  /**
	 *	Stores the current user id.
	 *  Used as a test to see if a user is logged in.
	 *	@access protected
	 *	@var int
	 */
  protected $user_id=null;
  
  /**
	 *	A simple flag, indicates whether passwords are encrypted
	 *	@access protected
	 *	@var boolean
	 */
  protected $encrypt_password=false;
  
  protected $session_key = "loggedin_user";
  
	function __construct($username=null, $password=null, $encrypted=false) {
	  if($username && $password) {
	    $this->verify($username, $password);
	  } elseif($sess_val = Session::get($this->session_key)) {
	    $this->user_id = $sess_val;
	    $this->verify(null, null, $this->user_id);
	  }
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

	/**
	 *	Sets a loggedin_user variable in the session.
	 *	@access public
	 *	@return bool
	 */  
  public function set_logged_in($user_id) {
	  if($this->user_id) {
	    return false;
	  } else {
	    $this->user_id = $user_id;
	  }
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
	 	$salt = "ABCDEFGHJKLMNPRSTUVWXYZ0123456789abchefghjkmnpqrstuvwxyz"; 
		srand((double)microtime()*1000000); 
  	for($i = 0;$i < $length;$i++) { 
  	  $num = rand() % 59; 
  		$tmp = substr($salt, $num, 1); 
  		$pass = $pass . $tmp; 
  	} 
	  return $pass; 
	}

  
  /**
	 *	This method is provided by the subclass
	 */
  abstract protected function verify($username, $password);
  
  function __destruct() {
    if($this->user_id) {
      Session::set($this->session_key, $this->user_id);
    } else {
      Session::unset_var($this->session_key);
    }
  }
	
}
?>