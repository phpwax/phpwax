<?php

class WXGenerator {
  
  public $output="";
  public $functions = array();
  public $destination;
  public $final_output="";
  
  public function __construct($generator_type, $args) {
    $this->{$generator_type}($args);
  }
  
  public function start_php_file($class_name, $extends=null, $functions=null) {
    $output = "<?php"."\n";
    $output.= "class $class_name";
    if($extends) $output.= " extends $extends"."\n";
      else $output.="\n";
    $output.="{"."\n";
    if($functions) {
      foreach($functions as $function) {
        $output.=$this->add_function($function);
      }
    }
    return $output;
  }
  
  public function end_php_file() {
    $output.="\n"."}"."\n";
    $output.="?>"."\n";
    return $output;
  }
  
  public function add_function($name, $access="public") {
    $output = "\n"."  $access function $name {"."\n"."\n";
    $output.= "  }"."\n";
    return $output;
  }
  
  public function add_line($text) {
    return "\n".$text."\n";
  }
  
  public function write_to_file($directory) {
    $this->final_output.= $this->end_php_file();
    $command = "echo ".'"'.$this->final_output.'"'." > ".$this->destination;
    system($command);
  }
  
  public function new_test($args) {
    $class = camelize($args[0], true);
    $this->final_output.= $this->start_php_file($class, "WXTestCase", array("setUp", "tearDown"));
    $this->final_output.= $this->add_line("  /* Add tests below here. all must start with the word 'test' */");
    $this->write_to_file(APP_DIR."tests/Test".$class.".php");
  }
  
  public function new_model($args) {
    $class = camelize($args[0], true);
    $this->final_output.= $this->start_php_file($class, "WXActiveRecord");
    $this->write_to_file(APP_DIR."model/".$class.".php");
  }
  
  public function new_controller($args) {
    $path = explode("/", $args[0]);
    $class = camelize(implode("_", $path), true)."Controller";
    if(count($path > 1)) {
     $path = $path[0]."/"; 
     system("mkdir -p ".APP_DIR."controller/$path}");
    } else $path = "";    
    $this->final_output.= $this->start_php_file($class, "ApplicationController");
    $this->write_to_file(APP_DIR."controller/".$path.$class.".php");
  }
  
  public function make_view($name, $files=null) {
    $command = "mkdir -p ".VIEW_DIR.$name;
    system($command);
  }
  
  
}