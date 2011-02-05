<?php
/**
  *  This file sets up the application.
  *  Sets up constants for the main file locations.
  *  @package PHP-Wax
  */

/**
 *  Defines application level constants
 */

require_once(FRAMEWORK_DIR."/Wax.php");
require_once(FRAMEWORK_DIR."/core/WaxEvent.php");
require_once(FRAMEWORK_DIR."/exceptions/WaxException.php");



function wax_autoload($class_name) {
  Wax::include_from_registry($class_name);
}
spl_autoload_register("wax_autoload");

function throw_wxexception($e) {
  $exc = new WaxException($e->getMessage(), "Application Error");
}

function throw_wxerror($code, $error) {
  $exc = new WaxException($error, "Application Error $code");
}



class WaxRecursiveDirectoryIterator extends RecursiveDirectoryIterator {
  
  public function hasChildren() {
    if(substr($this->getFilename(),0,1)==".") return false;
    else return parent::hasChildren();
  }
}

class WaxPluginResources{
  public static $plugin_asset_types = array('images'=>"images", 'javascripts'=>"javascripts", 'stylesheets'=>"stylesheets");
  
  public function rewrite(){
	  if(!isset($_GET["route"])) return false;
    include_once FRAMEWORK_DIR."/utilities/File.php";
	  $temp_route = $_GET["route"];
	  $_temp_route= preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $temp_route);
	  while(strpos($temp_route, "..")) $temp_route= str_replace("..", ".", $temp_route);
	  $asset_paths = explode("/", $_GET["route"]);
	  if(in_array($asset_paths[0], WaxPluginResources::$plugin_asset_types)) {
	    $plugins = scandir(PLUGIN_DIR);
	    $type = array_shift($asset_paths);
  	  rsort($plugins);
  	  foreach($plugins as $plugin) {
  	    if(!is_file($plugin) && substr($plugin,0,1) != "."){
	        $path = PLUGIN_DIR.$plugin."/resources/public/".$type."/".implode("/", $asset_paths);
	        $mime = File::mime_map($path);
	        if(is_readable($path)){
	          $mime = File::mime_map($path);
	          switch($type){
	            case "images": File::display_image($path);break;
	            default: File::display_asset($path, $mime); break;
            }
          }
        }
  	  }
	  }
  }
}
WaxEvent::add("wax.pre_init",function(){WaxPluginResources::rewrite();});


/**
 * A simple static class to Preload php files and commence the application.
 * It manages a registry of PHP files and includes them according to hierarchy.
 * All file inclusion is done 'just in time' meaning that file load overhead is avoided.
 * @package PHP-Wax
 * @static
 */
class AutoLoader {

	public static function __callStatic($method, $args) {
		return call_user_func_array(array("Wax", $method),$args);
	}

 
  
}
Wax::pre_initialise();

