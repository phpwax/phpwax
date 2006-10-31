<?php
/**
 * 	@package wx.php.core
 */

/**
	*	An abstract base class to handle basic authentication.
	* Can be extended to check access via a db or flat file.
 	* @package wx.php.core
  */
abstract class Authorise
{

	/**
	 *	A randomising key.
	 *	@access protected
	 *	@var string
	 */
  protected $secure_key='TESTING';

  
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
  
	function __construct($username=null, $password=null) {
	  if($username && $password) {
	    $this->verify($username, $password);
	  } elseif($sess_val = Session::get($this->session_key)) {
	    $this->user_id = $sess_val;
	  }
	}
	/**
	 *	Sees if a loggedin_user is set in the session.
	 *	@access public
	 *	@return bool
	 */ 
  public static function check_logged_in() { 
		if($this->user_id) {
		  return true;
		}
		return false;
  }
  
  public static function is_logged_in() {
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
  }

	/**
	 *	Makes a random password and encrypts using the built in method.
	 *	@access public
	 *	@return string
	 */
	public function getEncryptedPass($len){
		$pass = $this->makeRandomPassword($len);
		return $this->encrypt($pass);
	}
	
	/**
	 *	Encrypts a password using mcrypt.
	 *	@access public
	 *	@return string
	 *	@param string
	 */
	public function encrypt($string) {
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB); //get vector size on ECB mode 
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND); //Creating the vector
		$encrypted = mcrypt_encrypt (MCRYPT_RIJNDAEL_256, $this->secure_key, $string, MCRYPT_MODE_ECB, $iv);
	  return $encrypted;
	}

	/**
	 *	Decrypts a password using mcrypt.
	 *	@access public
	 *	@return string
	 *	@param string
	 */
	public function decrypt($string) {
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB); 
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypted = mcrypt_decrypt (MCRYPT_RIJNDAEL_256, $this->secure_key, $string, MCRYPT_MODE_ECB, $iv);
	    return rtrim($decrypted);
	}

	/**
	 *	Derypts a password using mcrypt.
	 *	@access protected
	 *	@return string
	 *	@param string
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
    }
  }
	
}


/**
	*	Extends the Authorise base class to handle database authentication.
  *  @package wx.php.core
  */
class DBAuthorise extends Authorise
{
	/**
	 *	Sets the default database table name.
	 *	@access public
	 *	@var string 
	 */
	public $database_table='User';
	public $username_column='username';
	public $password_column='password';
	protected $user_object=null;

	
	/**
	 *	Looks up the username and password in the database.
	 *	If there's a match, setup the user as logged in.
	 *	@access public
	 *	@param string $username
	 *	@param string $password
	 *	@return bool
	 */
	public function verify($username, $password) {
	  $user=new $this->database_table;
	  $method = "findBy".ucfirst($this->username_column)."And".ucfirst($this->password_column);
	  $current_user = $user->$method($username, $password);
		if($current_user = $user->$method($username, $password)) {
		  $current_user = $current_user[0];
		  print_r($current_user); exit;
		  $this->user_id = $current_user->id;
		  $this->user_object = $current_user;
		} else {
		  return false;
		}
	}
	
}

/**
	*	Extends the Authorise base class to handle flat file authentication.
	*	Users are configured in an array named 'users' inside the YAML config.
  *  @package wx.php.core
  */
class YamlAuthorise extends Authorise
{
	/**
	 *	Sets the default config file.
	 *	@access public
	 *	@var string 
	 */
	protected $configfile = "{APP_DIR}config/config.yml";


	/**
	 *	Verifies the supplied user/pass against the config file.
	 *	@access public
	 *	@param string $username
	 *	@param string $password
	 *	@return bool 
	 */	
	public function verify($username, $password) {
	  $this->config_array = Spyc::YAMLLoad($this->configfile);
	  $users_array=$this->config_array['users'];
	  $i=1;
	  if(count($users_array)>1) {
	    return false;
	  }
	  foreach($users_array as $user=>$pass) {
	    if($user==$username && $pass==$password) { 
		    $this->set_logged_in($i);
		    return true;
		  }
		$i++;
	  }	
	  return false;
	}
		
	
} 

