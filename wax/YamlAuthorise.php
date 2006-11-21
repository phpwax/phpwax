<?php

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
?>