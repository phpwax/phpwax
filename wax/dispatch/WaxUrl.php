<?php
/**
 * 
 *
 * @author Ross Riley
 * @package PHP-Wax
 **/

/**
 * Route construction class
 *
 * @package PHP-Wax
 * @author Ross Riley
 * 
 * This class allows urls to be mapped specifically to controllers actions and variables
 * It also requires access to the config object to check configurations.
 **/
class WaxUrl {
  
  /**
   *  This is simply a stackable array of mappings - new mappings are added to the top of the stack
   *  The lookup keeps going till it gets a match, falling back on the two defaults where necessary.
   *
   * @var array
   **/
  static $mappings = array(
    array("", array("controller"=>"page")),
    array(":controller/:action/:id")
  );



  
  /**
   *  Can be called from anywhere in the application. Maps a url to a particular outcome.
   * 
   *  Some examples.....
   *  WaxUrl::map(":controller/:action/:id") 
   *    - A default if no outcome is specified the variables after the colon are named with the values
   *
   *  WaxUrl::map(":controller/:action/:id", array("controller"=>"blog"))
   *    - A catch-all controller anything that can't find a controller will be mapped to the blog controller
   *
   *  WaxUrl:map("", array("controller"=>"page"))
   *    - Maps an empty url to a default controller - default action will be index but this can also be overwritten
   *
   *  WaxUrl::map("/tags/:tags*", array("controller"=>"tags", "action"=>"show"))
   *    - Looks for trigger pattern and then returns an array of the named parameter
   *
   *  WaxUrl::map("files/:file", array("controller"=>"file"), array("file"=>"^\w*\.doc|zip|jpg|gif"))
   *    - Using the conditions array allows you to provide a pattern that a parameter must match
   *
   * @return void
   **/
  
  static public function map($pattern, $outcome=array(), $conditions=array()) {
    array_unshift(self::$mappings, array($pattern, $outcome, $conditions));
  }
  
  
  /**
   *  Loops through the defined lookup patterns until one matches
   *  Uses the result to set the global $_GET parameters
   *  Any url variables that are explicitly set are ignored, this only works on the url portion
   *
   * @return void
   **/

  static public function perform_mappings($pattern) {
    foreach(self::$mappings as $map) {
      $pattern = explode("/", $map[0]);
      $subject = explode("/", $_GET['route']);
      for($i=0;$i< count($subject); $i++) {
        if($pattern[$i]==$subject[$i]) continue;
        
        if(substr($pattern[$i],0,1) ==":") {
          $_GET[substr($pattern[$i], 1)]=$subject[$i];
        }
        
      }
      $outcome = $map[1];
      $conditions = $map[2];
    }
  }
  
  /**
   * undocumented function
   *
   * @return mixed
   **/
  static public function get($val) {
    return $_GET[$val];
  }
  	
}

?>