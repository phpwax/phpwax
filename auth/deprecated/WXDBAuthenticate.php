<?php
/**
 * 	@package PHP-Wax
 */

/**
	*	An class to handle database authentication.
	* Can be extended to check access via a flat file or any other method.
 	* @package PHP-Wax
  */
class WXDBAuthenticate extends WaxAuthDb {
	
	/**
	 *	Sees if a loggedin_user is set in the session.
	 *	@access public
	 *	@return bool
	 */ 

  public function setup_user() {
    if($id = Session::get($this->session_key)) {
      $object = WXInflections::camelize($this->db_table, true);
      $user = new $object;
      $result = $user->find($id);
      if($result) {
        $this->user_object = $result;
        $this->user_id = $result->id;
      }
    }
  }

  public function verify($username, $password) {
    $object = WXInflections::camelize($this->db_table, true);
    $user = new $object;
    $method = "find_by_".$this->user_field."_and_".$this->password_field;
    if($this->encrypt) $password = $this->encrypt($password);
    $result = $user->$method($username, $password);
    if($result) {
      $this->user_object = $result;
      $this->user_id = $result->id;
      return true;
    }
    return false;
  }
	
}
