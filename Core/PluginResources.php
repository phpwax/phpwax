<?php
namespace Wax\Core;

class PluginResources{
  public static $plugin_asset_types = array('images'=>"images", 'javascripts'=>"javascripts", 'stylesheets'=>"stylesheets");
  
  public function rewrite(){
	  if(!isset($_GET["route"])) return false;
    include_once FRAMEWORK_DIR."/utilities/File.php";
	  $temp_route = $_GET["route"];
	  $_temp_route= preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $temp_route);
	  while(strpos($temp_route, "..")) $temp_route= str_replace("..", ".", $temp_route);
	  $asset_paths = explode("/", $_GET["route"]);
	  if(in_array($asset_paths[0], self::$plugin_asset_types)) {
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
