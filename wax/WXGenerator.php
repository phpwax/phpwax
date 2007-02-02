<?php

class WXGenerator {
  
  public $output="";
  public $functions = array();
  public $destination;
  public $final_output="";
	public $stdout = array();
  
  public function __construct($generator_type, $args=array()) {
		if(count($args)< 1) {
			$this->add_stdout("You must supply a $generator_type name that you wish to create.", "error");
			return false;
		}
		$method = "new_".$generator_type;
    $this->{$method}($args);
  }

	public function add_stdout($message, $type="info") {
		switch($type) {
			case "error": 	$this->stdout[]="[Error] ".$message; break;
			case "process": $this->stdout[]="...".$message; break;
			default:				$this->stdout[]=$message;
		}
	}
	
	private function run_shell($command) {
		$command = escapeshellcmd($command);
		return shell_exec($command);
	}
	
	public function add_perm_error($file) {
		$this->add_stdout("Couldn't create $file, maybe it already exists, or perhaps there's a permissions problem.");
	}
  
  public function start_php_file($class_name, $extends=null, $functions=array()) {
    $output = "<?php"."\n";
    $output.= "class $class_name";
    if($extends) $output.= " extends $extends"."\n";
      else $output.="\n";
    $output.="{"."\n";
      foreach($functions as $function) {
        $output.=$this->add_function($function);
      }
    return $output;
  }
  
  public function end_php_file() {
    $output ="\n"."}"."\n";
    $output.="?>"."\n";
    return $output;
  }
  
  public function add_function($name, $include_text=null, $visibility="public") {
    $output = "\n"."  $visibility function $name() {"."\n";
    if($include_text) $output.=$include_text;
    $output.= "  }"."\n";
    return $output;
  }
  
  static public function new_helper_wrapper($helper_class, $helper_name) {
    $code ="\$args = func_get_args();
  	        \$helper = new $helper_class();
  	        return call_user_func_array(array(\$helper, '$helper_name'), \$args);";
  	eval(self::add_function($helper_name, $code, ""));
  }
   
  public function add_line($text) {
    return "\n".$text."\n";
  }
  
  private function write_to_file($file) {
    $this->final_output.= $this->end_php_file();
    $command = "echo ".'"'.$this->final_output.'"'." > ".$file;
    passthru($command);
		if(is_readable($file)) return true;
		return false;
  }
  
  public function new_test($args=array()) {
		$class = "Test".WXInflections::camelize($args[0], true);
    $this->final_output.= $this->start_php_file($class, "WXTestCase", array("setUp", "tearDown"));
    $this->final_output.= $this->add_line("  /* Add tests below here. all must start with the word 'test' */");
    $res = $this->write_to_file(APP_DIR."tests/".$class.".php");
		if(!$res) {
			$this->add_perm_error("app/tests/Test".$class.".php");
	  	return false;
		}
		$this->add_stdout("Created test at app/tests/".$class.".php"); return true;
	}
  
  public function new_model($args=array()) {
		$class = WXInflections::camelize($args[0], true);
    $this->final_output.= $this->start_php_file($class, "WXActiveRecord");
    $res = $this->write_to_file(APP_DIR."model/".$class.".php");
		if(!$res) {
			$this->add_perm_error("app/model/".$class.".php"); 
			return false;
		}
		$this->add_stdout("Created model file at app/model/".$class.".php");
		$this->final_output="";
		$this->new_migration("create_".WXInflections::underscore($class), WXInflections::underscore($class) );
  }
  
  public function new_controller($args=array()) {
		$path = explode("/", $args[0]);
    $class = WXInflections::camelize(implode("_", $path), true)."Controller";
    if(count($path)> 1) {
     	$path = $path[0]."/";
     	mkdir(APP_DIR."controller/$path", 0755, true);
    } else $path = "";    
    $this->final_output.= $this->start_php_file($class, "ApplicationController");
    $res = $this->write_to_file(APP_DIR."controller/".$path.$class.".php");
		if(!$res) {
			$this->add_perm_error("app/controller/".$path.$class.".php"); 
			return false;
		}
		$this->add_stdout("Created controller file at app/controller/".$path.$class.".php");		
    $this->make_view($args[0]);
  }
  
  public function make_view($name) {
    mkdir(VIEW_DIR.$name, 0755, true);   	
		if(!is_dir(VIEW_DIR.$name)) {
			$this->add_perm_error("app/view/".$name); 
			return false;
		}
		$this->add_stdout("Created view folder at app/view/$name");
  }
  
  public function new_email($args=array()) {
		$class = WXInflections::camelize($args[0], true);
    $this->final_output.= $this->start_php_file($class, "WXEmail");
    $res = $this->write_to_file(APP_DIR."model/".$class.".php");
		if(!$res) {
			$this->add_perm_error("app/model/".$class.".php"); 
			return false;
		}
		$this->add_stdout("Created email file at app/model/".$class.".php");
		$this->make_view(WXInflections::underscore($class));
  }
  
  public function new_migration($name, $table=null) {
  	if(is_array($name)) $name = $name[0];
		$migrate = new WXMigrate;
    $migrate->increase_version_latest();
		$version = $migrate->get_version_latest();
    $class = WXInflections::camelize($name, true);
		$this->final_output.= $this->start_php_file($class, "WXMigrate");
    if($table) {
			$this->final_output.=$this->add_function("up", sprintf('    \$this->create_table(\"%s\");', $table)."\n");
			$this->final_output.=$this->add_function("down", sprintf('    \$this->drop_table(\"%s\");', $table)."\n");
		}
    else {
			$this->final_output.=$this->add_function("up");
			$this->final_output.=$this->add_function("down");
		}
    $file = str_pad($version, 3, "0", STR_PAD_LEFT)."_".WXInflections::underscore($class);
    $res = $this->write_to_file(APP_DIR."db/migrate/".$file.".php");
		if(!$res) {
			$this->add_perm_error("app/db/migrate/".$file.".php"); 
			return false;
		}
		$this->add_stdout("Created migration file at app/db/migrate/".$file.".php");
  }

	function __destruct() {
		foreach($this->stdout as $output_line) {
			echo $output_line."\n";
		}
	}
  
  
}