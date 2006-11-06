<?php

class WXGenerator {
  
  public $output="";
  public $functions = array();
  public $destination;
  public $final_output="";
	public $stdout = array();
  
  public function __construct($generator_type, $args) {
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
		$command = escapeshellarg($command);
		return shell_exec($command);
	}
	
	public function add_perm_error($file) {
		$this->add_stdout("Couldn't create $file, maybe it exists, or perhaps a permissions problem.");
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
  
  public function add_function($name, $include_text=null) {
    $output = "\n"."  public function $name() {"."\n"."\n";
    if($include_text) $output.=$include_text;
    $output.= "  }"."\n";
    return $output;
  }
  
  public function add_line($text) {
    return "\n".$text."\n";
  }
  
  public function write_to_file($file) {
    $this->final_output.= $this->end_php_file();
    $command = "echo ".'"'.$this->final_output.'"'." > ".$file;
    $this->run_shell($command);
		if(is_readable($file)) return true;
		return false;
  }
  
  public function new_test($args) {
		if(count($args < 1)) {
			$this->add_stdout("You must supply a test class name that you wish to create.", "error");
		}
    $class = WXInflections::camelize($args[0], true);
    $this->final_output.= $this->start_php_file($class, "WXTestCase", array("setUp", "tearDown"));
    $this->final_output.= $this->add_line("  /* Add tests below here. all must start with the word 'test' */");
    $res = $this->write_to_file(APP_DIR."tests/Test".$class.".php");
		if($res) $this->add_stdout("Created test at app/tests/Test".$class.".php"); return true;
		$this->add_perm_error("app/tests/Test".$class.".php");
  	return false;
	}
  
  public function new_model($args) {
		if(count($args < 1)) {
			$this->add_stdout("You must supply a model name that you wish to create.", "error");
		}
    $class = WXInflections::camelize($args[0], true);
    $this->final_output.= $this->start_php_file($class, "WXActiveRecord");
    $res = $this->write_to_file(APP_DIR."model/".$class.".php");
		if(!$res) $this->add_perm_error("app/model/".$class.".php"); return false;
		$this->add_stdout("Created model file at app/model/".$class.".php");
		$this->new_migration("create_".underscore($class), underscore($class) );
  }
  
  public function new_controller($args) {
		if(count($args < 1)) {
			$this->add_stdout("You must supply a controller name that you wish to create.", "error");
		}
    $path = explode("/", $args[0]);
    $class = WXInflections::camelize(implode("_", $path), true)."Controller";
    if(count($path > 1)) {
     $path = $path[0]."/"; 
     $this->run_shell("mkdir -p ".APP_DIR."controller/$path}");
    } else $path = "";    
    $this->final_output.= $this->start_php_file($class, "ApplicationController");
    $res = $this->write_to_file(APP_DIR."controller/".$path.$class.".php");
		if(!$res) $this->add_perm_error("app/controller/".$path.$class.".php"); return false;
		$this->add_stdout("Created controller file at app/controller/".$path.$class.".php");
    $this->make_view($path);
  }
  
  public function make_view($name) {
    $command = "mkdir -p ".VIEW_DIR.$name;
    $res = $this->run_shell($command);
		if(!$res) $this->add_perm_error("app/view/".$name); return false;
		$this->add_stdout("Created view folder at app/view/$name");
  }
  
  public function new_email($args) {
		if(count($args < 1)) {
			$this->add_stdout("You must supply a test name that you wish to create.", "error");
		}
    $class = WXInflections::camelize($args[0], true);
    $this->final_output.= $this->start_php_file($class, "WXEmail");
    $res = $this->write_to_file(APP_DIR."model/".$class.".php");
		if(!$res) $this->add_perm_error("app/model/".$path.".php"); return false;
		$this->add_stdout("Created email file at app/model/".$path.".php");
  }
  
  public function new_migration($name, $table=null) {
		if(count($args < 1)) {
			$this->add_stdout("You must supply a migration name that you wish to create.", "error");
		}
    if(is_array($name)) $name = $name[0];
    $migrate = new WXMigrate;
    $version = $migrate->increase_version_latest();
    $class = WXInflections::camelize($name, true);
    $this->final_output.= $this->start_php_file($class, "WXMigration");
    if($table) $this->add_function("up", "  \$this->create_table(\"$table\");");
    else $this->add_function("up");
		$this->add_function("down");
    $file = str_pad($new_version, 3, "0", STR_PAD_LEFT)."_".underscore($class);
    $res = $this->write_to_file(APP_DIR."db/migrate/".$file.".php");
		if(!$res) $this->add_perm_error("app/db/migrate/".$file.".php"); return false;
		$this->add_stdout("Created migration file at app/db/migrate/".$file.".php");
  }

	function __destruct() {
		foreach($this->stdout as $output_line) {
			echo $output_line."\n";
		}
	}
  
  
}