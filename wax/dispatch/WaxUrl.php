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
  static public $mappings = array(
    array(":action/:id"),
    array(":action")
  );
  
  static public $default_controller = "page";
  static public $default_action = "index";


  
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

  static public function perform_mappings() {
    self::detect_maintenance();
    foreach(self::$mappings as $map) {
      $left = $map[0];
      $right = $_GET["route"];
      $left = preg_replace("/:([A-Za-z0-9\-]*\*)/", "([A-Za-z0-9.-/]*)", $left);
      $left = preg_replace("/:([A-Za-z0-9\-]*)/", "([A-Za-z0-9.-]*)", $left);
      $left = str_replace("/", "\/", $left);  
      if($left===$right && !strpos($left,":")) $mapped_route = $map[1];
      elseif(preg_match("/".$left."/", $right, $matches)) {
        if(!$_GET["controller"] && !$map[1]["controller"]) {
          self::route_controller();
        } else {

          $mappings = split("/", $map[0]);
          array_shift($matches);
          while(count($mappings)) {
            if($mappings[0]==$matches[0]) {
              array_shift($matches);
            } elseif(substr($mappings[0],0,1)==":" && substr($mappings[0],-1)=="*") {
              $mapped_route[substr($mappings[0],1, -1)]=explode("/", $matches[0]);
            } elseif(substr($mappings[0],0,1)==":") {
              $mapped_route[substr($mappings[0],1)]=$matches[0];
              array_shift($matches); 
            }
            array_shift($mappings);
          }
        }
        $mapped_route = array_merge($mapped_route, (array) $map[1]);
      }
      // Map against named parameters in options array
      
      if($mapped_route) {
        foreach($mapped_route as $k=>$val) {
          $_GET[$k]=$val;
        }
      break;
      }
    }
    self::force_defaults();
  }
  
  /**
   * get function
   *
   * @return mixed
   **/
  static public function get($val) {
    self::perform_mappings();
    return $_GET[$val];
  }
  
  
  /**
    *  Checks whether a file exists for the named controller
    *  @return boolean      If file exists true
    */
	protected function route_controller() {
	  $route = split("/", $_GET["route"]);
	  while(count($route) >0) {
	    if(self::is_controller(join("/",$route))) {$controller = join("/",$route); break;}
	    if(!$controller) array_pop($route);
	  }
	  if($controller) {
	    $_GET["controller"]=$controller;
	    $_GET["route"]=str_replace($controller, "", $_GET["route"]);
	    $_GET["route"]=ltrim($_GET["route"], "/");
	  }
	}
	
	protected function is_controller($test) {
	  $path = "";
	  if(strpos($test, "/")) {
			$path = substr($test, 0, strpos($test, "/")+1);
		}
	  if(is_file(CONTROLLER_DIR.$path.Inflections::slashcamelize($test, true)."Controller.php")) return true;
	  return false;
	}
	
  protected function force_defaults() {
    if(!$_GET["controller"]) $_GET["controller"]=self::$default_controller;
    if(!$_GET["action"]) $_GET["action"]=self::$default_action;
  }
  
  protected function detect_maintenance() {
	  $maintenance = Config::get("maintenance");
	  if($maintenance['ip'] && $maintenance['redirect']) {
	    if($_SERVER['REMOTE_ADDR']==$maintenance['ip']) return false;
	    if($_GET["route"] != $maintenance['redirect']) $_GET["route"]=$maintenance['redirect'];
	    else return false;
	    return true;
	  }
	  return false;
	}
  	
}

?>
