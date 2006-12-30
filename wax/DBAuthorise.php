<?php
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
	public $user_object=null;

	
	/**
	 *	Looks up the username and password in the database.
	 *	If there's a match, setup the user as logged in.
	 *	@access public
	 *	@param string $username
	 *	@param string $password
	 *	@return bool
	 */
	public function verify($username, $password, $id=null) {
	  if($id) {
	    $user=new $this->database_table($id);
		  $this->user_object = $user;
		  return true;
	  } elseif($username && $password) {
	    $user=new $this->database_table;
  	  $method = "find_by_".$this->username_column."_and_".$this->password_column;
  	  $current_user = $user->{$method}($username, $password);
  		if(count($current_user)==1) {
  		  $this->user_id = $current_user->id;
  		  $this->user_object = $current_user;
  		  return true;
  		} else {
  		  return false;
  		}
		}
		return false;
	}
	
}
?>