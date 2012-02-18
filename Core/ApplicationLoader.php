<?php

namespace Wax\Core;

class ApplicationLoader extends Loader {
  public $constants =  array(
     'APP_DIR' => array('parent'=>'WAX_ROOT', 'value'=>'app/'),
     'MODEL_DIR' => array('parent'=>'APP_DIR', 'value'=>'model/'),
     'CONTROLLER_DIR' => array('parent'=>'APP_DIR', 'value'=>'controller/'),
     'FORMS_DIR' => array('parent'=>'APP_DIR', 'value'=>'forms/'),
     'CONFIG_DIR' => array('parent'=>'APP_DIR', 'value'=>'config/'),
     'VIEW_DIR' => array('parent'=>'APP_DIR', 'value'=>'view/'),
     'APP_LIB_DIR' => array('parent'=>'APP_DIR', 'value'=>'lib/'),
     'TMP_DIR' => array('parent'=>'WAX_ROOT', 'value'=>'tmp/'),
     'CACHE_DIR' => array('parent'=>'TMP_DIR', 'value'=>'cache/'),
     'LOG_DIR' => array('parent'=>'TMP_DIR', 'value'=>'log/'),
     'SESSION_DIR' => array('parent'=>'TMP_DIR', 'value'=>'session/'),
     'PUBLIC_DIR' => array('parent'=>'WAX_ROOT', 'value'=>'public/'),
     'SCRIPT_DIR' => array('parent'=>'PUBLIC_DIR', 'value'=>'javascripts/'),
     'STYLE_DIR' => array('parent'=>'PUBLIC_DIR', 'value'=>'stylesheets/'),
     'PLUGIN_DIR' => array('parent'=>'WAX_ROOT', 'value'=>'plugins/')
  );
  
  public $directory_registry = array(
    "APP_DIR",
    "MODEL_DIR",
    "CONTROLLER_DIR",
    "FORMS_DIR",
    "APP_LIB_DIR"
  );
  
  public function map_file($class) {
    $pos = strrpos($class, '\\')?:0;
    if($pos > 0) {
      $namespace = substr($class, 0, $pos);
      $class_name = substr($class, $pos + 1);
    } else {
      $namespace="";
      $class_name = $class;
    }
    $normalized_class = str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR.str_replace('_', DIRECTORY_SEPARATOR, $class_name).'.php';
    return ltrim($normalized_class, "/");
  }
  
  
  
  public function load($class){
    $file = $this->map_file($class);
    try {
      $res = FALSE;
      foreach($this->directory_registry as $dir) {
        if(is_readable(constant($dir).$file)) $res = include(constant($dir).$file); 
      }
      if($res) return TRUE;
      if($res = $this->include_from_fallbacks($class)) return $res;
		} catch (Exception $e) {        				
      return FALSE;
		}
    return FALSE;
  }
  
  

}
