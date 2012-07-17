<?php


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
  const REGEX_SEG = "(?P<$1>[^/.,;?]+)";
  const REGEX_CATCHALL = "(?P<$1>[^.,;?]+)";
	const REGEX_URL  = '[A-Za-z0-9-_]';

  /**
   *  This is simply a stackable array of mappings - new mappings are added to the top of the stack
   *  The lookup keeps going till it gets a match, falling back on the two defaults where necessary.
   *
   * @var array
   **/
  static public $mappings = array(
    array(":action/:id/:params*"),
    array(":action/:id"),
    array(":action"),
    array("")
  );

  static public $defaults = array("controller"=>"page", "action"=>"index");
  static public $params = false;
  static public $mapped=false;
  static public $uri = false;
  static public $original_route = false;

  /**
   *
   *  This method Can be called from anywhere in the application. Maps a url to a particular outcome.
   *
   *###  URL Mapping Examples
   *
   *  `WaxUrl::map(":controller/:action/:id")`
   *  A default if no outcome is specified the variables
   *  after the colon are named with the values
   *
   *  `WaxUrl::map(":controller/:action/:id", array("controller"=>"blog"))`
   *  A catch-all controller anything that can't find a controller will
   *  be mapped to the blog controller.
   *
   *  `WaxUrl:map("", array("controller"=>"page"))`
   *  Maps an empty url to a default controller - default action will
   *  be index but this can also be overwritten
   *
   *  `WaxUrl::map("tags/:tags*", array("controller"=>"tags", "action"=>"show"))`
   *  Looks for trigger pattern and then returns an array of the named parameter
   *
   *
   * @return void
   **/

  static public function map($pattern, $outcome=array(), $conditions=array()) {
    array_unshift(self::$mappings, array($pattern, $outcome, $conditions));
  }

  /*
   *    Compiles syntax in defined routes to simple regular expressions.
   *
  **/

  static public function compile() {
    /* Setup */
    if(!self::$uri) self::$uri = $_GET["route"];
    if(!self::$original_route) self::$original_route = $_GET["route"];

    /*** Get the raw URI and try to map a controller *****/
    self::$params["controller"] = self::route_controller(self::$uri);
    self::$uri = ltrim(preg_replace("/".str_replace("/","\/",self::$params["controller"])."/","", self::$uri,1),"/");

    /* This part converts placeholders to normal regular expressions */
    foreach(self::$mappings as &$map) {
      $map[0] = preg_replace("/:(".self::REGEX_URL."*)\*/", self::REGEX_CATCHALL,$map[0]);
      $map[0] = preg_replace("/:(".self::REGEX_URL."*)/", self::REGEX_SEG,$map[0]);
    }
    //before mappings get the format
    if(!self::$params["format"] && preg_match("/(.*)\.(.*)/", self::$uri, $matches)){
      self::$params["format"] = $matches[2];
    }

    /**** This part matches the url against the regular expressions *******/
    foreach(self::$mappings as &$map) {

      /**** This line makes sure that the controller has been stripped from the uri ****/
      $map[0] = ltrim(str_replace(self::$params["controller"],"", $map[0]),"/");

      if(preg_match("#$map[0]#", self::$uri, $matches)) {
        if($map[1]["controller"]) self::$params["controller"]=$map[1]["controller"];

        /*** We make the final params by merging the defaults with the matches *********/
        self::$params = array_merge($_GET, (array)self::$params, (array)$matches, (array)$map[1]);
        self::force_defaults();
        return self::$mapped = true;
      }
    }
  }


  /**
   *  Builds a url based on the current route.
   *  Takes an optional array of options to override the current.
   *
   * @return string
   **/

  static public function build($options = array()) {
    $url = array();
    if($options["controller"]) $url[]=$options["controller"]; else $url[]=rtrim(self::$params["controller"],"/");
    if($options["action"]) $url[]=$options["action"]; elseif(self::$params["action"]) $url[]=self::$params["action"];
    if($options["id"]) $url[]=$options["id"]; elseif(self::$params["id"]) $url[]=self::$params["id"];
    return "/".join("/",$url);
  }

  static public function build_url($options = array()) {return self::build($options);}


  /**
   *  Loops through the defined lookup patterns until one matches
   *  Any url variables that are explicitly set are ignored, this only works on the url portion
   *
   * @return void
   **/

  static public function perform_mappings() {
    self::compile();
  }

  /**
   * get function
   *
   * @return mixed
   **/
  static public function get($val) {
    if(!self::$mapped) self::compile();
    if($val !=="controller" && strpos(self::$params[$val], "/")) return explode("/",self::$params[$val]);
    return self::$params[$val];
  }

  static public function get_params() {
    if(!self::$mapped) self::compile();
    return self::$params;
  }


  /**
    *  Checks whether a file exists for the named controller
    *  @return boolean      If file exists true
    */
	public static function route_controller($input = false) {
    if(!$input && preg_match("/(.*)\.(.*)/", self::$uri, $matches) && $matches[2]) $input = $matches[1]; // drop out format to route multilevel controllers with format correctly
	  if(!$input) $route = explode("/", self::$params["route"]);
	  else $route = explode("/", $input);
	  $controller = false;
	  while(count($route) >0) {
	    if(self::is_controller(join("/",$route))) {$controller = join("/",$route); break;}
	    if(!$controller) array_pop($route);
	  }
	  if($controller && !$input) {
	    self::$params["controller"]=$controller;
	    self::$params["route"]=preg_replace("/" . preg_quote($controller, "/") . "/", "", self::$params["route"], 1);
	    self::$params["route"]=ltrim(self::$params["route"], "/");
	  } elseif($controller && $input) return $controller;
    return false;
	}

	protected static function is_controller($test) {
	  if(class_exists(Inflections::slashcamelize(str_replace("-", "", $test), true)."Controller", false)) return true;
	  $path = "";
	  if(strpos($test, "/")) {
			$path = substr($test, 0, strpos($test, "/")+1);
		}
		foreach(Autoloader::controller_paths() as $cont) {
		  $search = $cont.$path.Inflections::slashcamelize($test, true)."Controller.php";
		  if(is_file($search)) return true;
		}
		return false;
	}

  protected static function force_defaults() {
    if(!self::$params["controller"]) self::$params["controller"]=self::$defaults["controller"];
    if(!self::$params["action"]) self::$params["action"]=self::$defaults["action"];
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

