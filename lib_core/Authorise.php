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
	 *	An array of users to check for access.
	 *	@access public
	 *	@var array
	 */
  public $users_array;
	/**
	 *	An randomising key.
	 *	@access protected
	 *	@var string
	 */
  protected $secureKey='TESTING';
	
	/**
	 *	Sees if a loggedin_user is set in the session.
	 *	@access public
	 *	@return bool
	 */ 
  public static function check_logged_in()
  { 
		if(Session::get('loggedin_user')) {
			return true;
		} else {
		return false;
		}
  }

	/**
	 *	Sets a loggedin_user variable in the session.
	 *	@access public
	 *	@return bool
	 */  
  public function set_logged_in($user_id)
  {
	  if(!Session::isset_var('loggedin_user') && Session::set('loggedin_user', $user_id) )  {
        return true;
      } else {
	    return false;
      }	
  }

	/**
	 *	Logs out a user by unsetting loggedin_user variable in the session.
	 *	@static
	 *	@access public
	 *	@return bool
	 */
  public static function logout()
  {
	if(Session::isset_var('loggedin_user')) { 
	  if(Session::unset_var('loggedin_user')) { 
		  return true;
	  }
	} 
	return true;	
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
		$encrypted = mcrypt_encrypt (MCRYPT_RIJNDAEL_256, $this->secureKey, $string, MCRYPT_MODE_ECB, $iv);
	  return $encrypted;
	}

	/**
	 *	Derypts a password using mcrypt.
	 *	@access public
	 *	@return string
	 *	@param string
	 */
	public function decrypt($string) {
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB); 
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypted = mcrypt_decrypt (MCRYPT_RIJNDAEL_256, $this->secureKey, $string, MCRYPT_MODE_ECB, $iv);
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
	public  $table='User';
	/**
	 *	Sets whether verification is to be carried out on a
	 *	plaintext or encrypted password.
	 *	@access private
	 *	@var string 
	 */
	private $plaintext_verify=false;
	
	/**
	 *	Looks up the username and password in the database.
	 *	If there's a match, setup the user as logged in.
	 *	@access public
	 *	@param string $username
	 *	@param string $password
	 *	@return bool
	 */
	public function verify($username, $password)
	{
	  $users=new $this->table;
		  if($username) { 
	    	  $this->users_array=$users->findByUsername($username);
	      } else { 
		    return false;
		  }
		
	  $i=1;
	  if(!$this->plaintext_verify) {
	  	$password=$this->encrypt($password);
	  }
	  foreach($this->users_array as $user) {
	    if($user->username==$username && $user->password==$password) { 
		  $this->set_logged_in($i);
		  return true;
		}
		$i++;
	  }
	  $errors[]='Invalid username or password';
	  Session::set('errors', $errors);	
	  return false;
	}

	/**
	 *	Looks up the username and password in the database.
	 *	This method uses plaintext verification for unencrypted passwords.
	 *	@access public
	 *	@param string $username
	 *	@param string $password
	 *	@return bool
	 */	
	public function plaintext_verify($username, $password)
	{
		$this->plaintext_verify=true;
		return $this->verify($username, $password);
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
	public $configfile;

	/**
	 *	Sets up the default config file.
	 *	@access public
	 *	@return void 
	 */	
	function __construct()
	{
		$this->configfile=APP_DIR.'config/config.yml';
	}

	/**
	 *	Verifies the supplied user/pass against the config file.
	 *	@access public
	 *	@param string $username
	 *	@param string $password
	 *	@return bool 
	 */	
	public function verify($username, $password)
	{
	  $this->config_array = Spyc::YAMLLoad($this->configfile);
	  $this->users_array=$this->config_array['users'];
	
	$i=1;
	  foreach($this->users_array as $user=>$pass)
      {
	      if($user==$username && $pass==$password) { 
		    $this->set_logged_in($i);
		    return true;
		  }
		$i++;
	    }
	  $errors[]='Invalid username or password';
	  Session::set('errors', $errors);	
	  return false;
	}
		
	
} 

