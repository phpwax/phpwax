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
    array(":action/:id/:params*"),
    array(":action/:id"),
    array(":action")
  );
  
  static public $default_controller = "page";
  static public $default_action = "index";
  static public $params = false;


  
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
   *  WaxUrl::map("tags/:tags*", array("controller"=>"tags", "action"=>"show"))
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
   *  Any url variables that are explicitly set are ignored, this only works on the url portion
   *
   * @return void
   **/

  static public function perform_mappings() {
    if(!self::$params) self::$params = $_GET;
    self::detect_maintenance();
    
    //before mappings get the format
    if(preg_match("/(.*)\.(.*)/", self::$params["route"], $matches)){
      self::$params["format"] = $matches[2];
      self::$params["route"] = $matches[1];
    }
    
    //before mappings build a route array
    self::$params["route_array"] = explode("/", self::$params["route"]);
    
    foreach(self::$mappings as $map) {
      $left = $map[0];
      $right = self::$params["route"];
      if(substr($right,-1)=="/") $right = substr($right, 0,-1);
      $left = preg_replace("/:([A-Za-z0-9\-_]*\*)/", "([A-Za-z0-9.\-/_]*)", $left);
      $left = preg_replace("/:([A-Za-z0-9\-_]*)/", "([A-Za-z0-9.\-_]*)", $left);
      $left = str_replace("/", "\/", $left);  
      if($left===$right && !strpos($left,":")) $mapped_route = $map[1];
      elseif(preg_match("/".$left."/", $right, $matches)) {
        if(!self::$params["controller"] && !$map[1]["controller"]) {
          self::route_controller();
          break;
        }

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
        $mapped_route = array_merge($mapped_route, (array) $map[1]);
      }
      // Map against named parameters in options array
     
      if($mapped_route) {
        foreach($mapped_route as $k=>$val) {
          self::$params[$k]=$val;
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
    return self::$params[$val];
  }
  
  static public function get_params() {
    self::perform_mappings();
    return self::$params;
  }
  
  
  /**
    *  Checks whether a file exists for the named controller
    *  @return boolean      If file exists true
    */
	public function route_controller($input = false) {
	  if(!$input) $route = split("/", self::$params["route"]);
	  else $route = split("/", $input);
	  while(count($route) >0) {
	    if(self::is_controller(join("/",$route))) {$controller = join("/",$route); break;}
	    if(!$controller) array_pop($route);
	  }
	  if($controller && !$input) {
	    self::$params["controller"]=$controller;
	    self::$params["route"]=preg_replace("/" . preg_quote($controller, "/") . "/", "", self::$params["route"], 1);
	    self::$params["route"]=ltrim(self::$params["route"], "/");
	  } elseif($controller && $input) return $controller;
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
    if(!self::$params["controller"]) self::$params["controller"]=self::$default_controller;
    if(!self::$params["action"]) self::$params["action"]=self::$default_action;
  }
  
  protected function detect_maintenance() {
	  $maintenance = Config::get("maintenance");	
		$redirect = false;
		//maintenace is setup
		if($maintenance['ip'] && $maintenance['redirect']){
			$redirect = true;
			//if an exlucsion ip is set the check the remote address - reset the flag
			if(is_array($maintenance['ip'])){
				foreach($maintenance['ip'] as $ip){
					if( preg_match("/".preg_quote($ip)."/i", $_SERVER['REMOTE_ADDR']) ) $redirect = false;
				} 
			}elseif(preg_match("/".preg_quote($maintenance['ip'])."/i", $_SERVER['REMOTE_ADDR']) ) $redirect = false;			
		}
		if($redirect) throw new MaintenanceException();
				
	}
  	
}

