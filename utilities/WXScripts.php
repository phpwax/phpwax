<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
 
class WXScripts {
  
  public $output=array();
  
  public function __construct($type, $argv=array()) {
    if(isset($_SERVER["SHELL"])) {
      ob_end_clean();
      define("IN_CLI", "true");
      error_reporting(E_PARSE);
    }
    $this->$type($argv);
  }
  
  protected function app_setup() {
    //if(!defined("ENV")) define("ENV", "development");
    Autoloader::run_application(ENV, false);
  }
  
  public function controller($argv) {
    $gen = new WXGenerator("controller", array_slice($argv, 1));
  }
  
  public function email($argv) {
    $gen = new WXGenerator("email", array_slice($argv, 1));
  }
  
  public function test($argv) {
    $this->app_setup();
    $gen = new WXGenerator("test", array_slice($argv, 1));
  }
  
  public function docs($argv) {
    $output_dir = WAX_ROOT."doc";
    $source_dir1 = WAX_ROOT."app";
    $source_dir2 = FRAMEWORK_DIR;
    $command = "phpdoc -t {$output_dir} -d {$source_dir1},{$source_dir2} -o HTML:frames:phpedit";
    system($command);
    $this->add_output("Documentation has been created in /doc. Open the index.html file to view.");
  }
  
  public function model($argv) {
    $this->app_setup();
    $gen = new WXGenerator("model", array_slice($argv, 1));
  }
  
  public function form($argv) {
    $this->app_setup();
    $gen = new WXGenerator("form", array_slice($argv, 1));
  }
  
  public function migration($argv) {
    $this->app_setup();
    $gen = new WXGenerator("migration", array_slice($argv, 1));
  }
  
  public function data($argv) {
    if(!$argv[1]) $this->fatal_error("[ERROR] You must give a data command to run.");
    $this->app_setup();
    $db = WXConfiguration::get('db');
    if($argv[1] == "save" && $this->get_response("About to write database to app/db/data.sql. This will overwrite previous versions!", "y")) {
      $command = "mysqldump ".$db['database']." --skip-comments --add-drop-table --ignore-table=".$db['database'].".migration_info -u".$db['username']." -p".$db['password']." > app/db/data.sql";
      system($command);
      $this->add_output("Data has been saved - use script/data load to restore.");
    }
    if($argv[1] == "load" && $this->get_response("About to overwrite database with data from app/db/data.sql!", "y")) {
      $command = "mysql ".$db['database']." -u".$db['username']." -p".$db['password']." < app/db/data.sql";
      system($command);
      $this->add_output("Data has been saved - use script/data load to restore.");
    }
    
  }

  
  public function run_tests($argv) {
    error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
    define("ENV", "test");
    $this->app_setup();
    define("CLI_ENV", true);
    if((!include 'simpletest/unit_tester.php') 
      || (!include 'simpletest/mock_objects.php')
      ) {
      throw new WXDependencyException("Simpletest library required. Install it somewhere in the include path", "Simpletest Dependency Failure");
    }

    if($argv[1] == "wax") {
      $testdir = FRAMEWORK_DIR."/tests";
    } elseif($argv[1] && is_dir(PLUGIN_DIR.$argv[1]."/tests")) {
      $testdir = PLUGIN_DIR.$argv[1]."/tests";
    } else {
      $testdir = APP_DIR."tests";
    }
    AutoLoader::include_dir($testdir);
    $test = new GroupTest('All tests');
    foreach(scandir($testdir) as $file) {  	 
      if(substr($file, -3)=="php" && substr($file,0,1)!=".") { 
        $class = substr($file, 0,-4);	
        $test->addTestClass($class); 	 
      } 	 
    }
    if(TextReporter::inCli()) {
      exit( $test->run(new TextReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
  }
  

  
  public function syncdb($argv) {
    if($argv[1] && ($argv[1]=="test" || $argv[1] == "production")) define("ENV", $argv[1]);
    $this->app_setup();
    foreach(Autoloader::$plugin_array as $plugin) {
      Autoloader::recursive_register(PLUGIN_DIR.$plugin["name"]."/lib/model", "plugin", true); 
    }
    Autoloader::include_dir(MODEL_DIR, true);
    foreach(get_declared_classes() as $class) {
      if(is_subclass_of($class, "WaxModel")) {
        $class_obj = new $class;
        $output = $class_obj->syncdb();
        if(strlen($output)) $this->add_output($output);
      }
    }
  }
  
  
  public function deploy($argv) {
    $deployment_settings = WXConfiguration::get('deploy');
    $remote = new WXRemote($deployment_settings['user'], $deployment_settings['server']);
    if(is_array($deployment_settings['before_deploy'])) {
      foreach($deployment_settings['before_deploy'] as $before) $remote->add_command($before);
    }
    $remote->svn_export($deployment_settings['svn_path'], $deployment_settings['server_path'], 
      $deployment_settings['svn_user'], $deployment_settings['svn_pass']);
    if(is_array($deployment_settings['after_deploy'])) {
      foreach($deployment_settings['after_deploy'] as $after) $remote->add_command($after);
    }
    $remote->run_commands();
    $this->add_output("Application successfully deployed to ".$deployment_settings['server']);
  }
  
  public function remote($argv) {
    $deployment_settings = WXConfiguration::get('deploy');
    $remote = new WXRemote($deployment_settings['user'], $deployment_settings['server']);
    $remote->add_command($argv[1]);
    $remote->run_commands();
  }
  
  public function runner($argv) {
    if(isset($argv[3]) && $argv[3]=="production") {
      define("ENV", "production");
      unset($argv[3]);
    }    
    $this->app_setup();
    if(!isset($argv[1]) || !isset($argv[2])) {
      exit("[ERROR] You must supply at least two values, a model and a method"."\n");
    }        
    $model_name = Inflections::camelize($argv[1], true);
    if(!class_exists($model_name) || !$model = new $model_name) {
      exit("[ERROR] Cannot find class name $model_name"."\n");
    }
    $commands = array($model_name, $argv[2]);
    array_shift($argv);
    array_shift($argv);
    array_shift($argv);
    
    if(call_user_func_array($commands, $argv) ) {
      $this->add_output("...successfully ran command, and method returned true.");
    } else {
      $this->add_output("...successfully ran command but method returned false.");
    }
  }

  
  protected function get_response($question, $positive="y", $options=false) {
    if(!$options) $options = "[y/n] ";
    echo $question . $options;
    $response = strtolower(trim(fgets(STDIN)));
    if($response == $positive) return true;
    return false;
  }
  
  protected function add_output($output) {
    $this->output[]=$output;
  }
  
  protected function fatal_error($output) {
    foreach($this->output as $out) {
      echo $out."\n";
    }
    echo $output."\n";
    exit;
  }
  
  public function __destruct() {
    foreach($this->output as $out) {
      echo $out."\n";
    }
  }
  
}

?>