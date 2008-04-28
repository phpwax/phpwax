<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
 
class WXScripts {
  
  public $output=array();
  public $plugin_array = array(
    "cms"=>"svn://php-wax.com/svn/plugins/cms/",
    "cms-email"=>"svn://php-wax.com/svn/plugins/subscription_manager/",
    "cms-ecom"=>"svn://php-wax.com/svn/plugins/cms-ecom/",
		"googlemap"=>"svn://php-wax.com/svn/plugins/googlemap/"
  );
  
  
  
  
  public function __construct($type, $argv) {
    ob_end_clean();
    //error_reporting(0);
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
  
  public function plugin($argv) {    
    array_splice($argv,1,0,"cold_install");
    $this->plugins($argv);
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
  
  public function plugins($argv) {
    if(!$argv[1]) $this->fatal_error("[ERROR] You must give a plugin command to run.");
    if(!$argv[2]) $this->fatal_error("[ERROR] You must give a plugin package name or url.");
    switch($argv[1]) {
      case "install":
        $this->plugin_install($argv[2], $argv[3], $argv[4]);
        break;
      case "migrate":
        $this->plugin_migrate($argv[2]);
        break;
      case "setup":
        $this->plugin_post_setup($argv[2]);
        break;
      case "cold_install":
        $this->plugin_install($argv[2]);
        $this->plugin_migrate($argv[2]);
        $this->plugin_post_setup($argv[2]);
        break;
      case "syncdb":
        $this->plugin_syncdb($argv[2]);
        break;
    }
  }
  
  protected function plugin_install($name, $source = false, $version = false) {
    $output_dir = PLUGIN_DIR.$name;
    if(!$source) {
      echo("Not specifying a release will install the development version.... These releases may not be stable")."\n";
      echo("Try using script/plugin install $name release <version>")."\n";
      if(!$this->get_response("Continue installing development version?", "y")) exit;
      $source = "svn://php-wax.com/svn/plugins/".$name."/trunk/";
    }
    elseif(($source=="tag" || $source=="release") && $version) {
      $source = "svn://php-wax.com/svn/plugins/".$name."/tags/".$version."/";
    }
    if($this->get_response("This will overwrite files inside the plugin/{$name} directory. Do you want to continue?", "y")) {
      File::recursively_delete(PLUGIN_DIR.$name);
      $command = "svn export -q {$source} {$output_dir} --force";
      system($command);
      $this->add_output("Plugin installed in /plugins/{$name}");
    }
  }
  
  protected function plugin_post_setup($name) {
    $this->app_setup();
    if(is_readable(PLUGIN_DIR.$name."/installer") && 
      $this->get_response("This plugin has an additional installer, would you like to run it?", "y")) {
      include(PLUGIN_DIR.$name."/installer");
      $this->add_output("Plugin installer ran.");
    }      
  }
  
  protected function plugin_migrate($dir) {
    if(!is_dir(PLUGIN_DIR.$dir)) $this->fatal_error("[ERROR] That plugin is not installed.");
    if(!$this->get_response("About to run database setup is this ok?", "y")) return false;
    $this->app_setup();
    $migrate_dir = PLUGIN_DIR.$dir."/migrate/";
    $migrate = new WXMigrate;
    $migrate->version_less_migrate($migrate_dir);
    $this->add_output("Plugin database setup completed");
  }
  
  protected function plugin_syncdb($dir) {
    if(!is_dir(PLUGIN_DIR.$dir)) $this->fatal_error("[ERROR] That plugin is not installed.");
    if(!$this->get_response("About to run database setup is this ok?", "y")) return false;
    $this->app_setup();
    $syncdir = PLUGIN_DIR.$dir."/lib/model";
    $this->add_output("Running sync from ".$syncdir);
    $this->syncdb($syncdir);
    $this->add_output("Plugin database has been synchronised");
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
  
  public function freeze($argv) {
    if(is_dir(WAX_ROOT."wax")) {
    	$this->add_output("[ERROR] Wax is already in your app directory. Delete it to freeze a new version.");
    	return false;
    } elseif(defined("WAX_EDGE")) {
    	$command = "svn export svn://php-wax.com/home/phpwax/svn/main/trunk/wax/ ".WAX_ROOT."wax";
    	system($command);
    } else {
    	$command = "svn export svn://php-wax.com/home/phpwax/svn/main/tags/".WAX_VERSION."/wax/ ".WAX_ROOT."wax";
    	system($command);
    }
  }
  
  public function syncdb($dir=false) {
    if($dir[1] && ($dir[1]=="test" || $dir[1] == "production")) define("ENV", $dir[1]);
    $this->app_setup();
    if($dir && is_dir($dir)) Autoloader::include_dir($dir, true);
    else Autoloader::include_dir(MODEL_DIR, true);
    foreach(get_declared_classes() as $class) {
      if(is_subclass_of($class, "WaxModel")) {
        $class_obj = new $class;
        $output = $class_obj->syncdb();
        $this->add_output($output);
      }
    }
  }
  
  public function migrate($argv) {
    if(isset($argv[1]) && $argv[1]=="test" || $argv[1] == "production") {
      define("ENV", $argv[1]);
    	unset($argv[1]);
    } elseif(isset($argv[1]) && $argv[1] =="directory" && isset($argv[2])) {
      $this->app_setup();
      $migrate = new WXMigrate(true);
      $direction = "up";
      if(isset($argv[3])) $direction="down";
      $result = $migrate->version_less_migrate($argv[2], $direction, true); 
      exit($result."\n");
    } 

    $this->app_setup();
  	
    $dbdir = WAX_ROOT.'app/db/migrate/';
    if(!is_dir($dbdir)) {
      $command = "mkdir -p $dbdir";
      system($command);
    }
    $version = false;
    if(isset($argv[1])) {
      $version = $argv[1];
    }

    $migrate = new WXMigrate;
    if($version == 'version') {
      $this->add_output("Now at version ".$migrate->get_version());
      return false;
    }
    if($version == 'clean') {
      $result = $migrate->migrate_revert($dbdir);
      $this->add_output("Database reset to version ".$result);
      return false;
    }
    $result = $migrate->migrate($dbdir, $version);
    if($result===false) {
      $this->add_output("No Files to migrate");
      return false;
    }
    $this->add_output("-------------------");
    $this->add_output("Successfully migrated to version ".$result);
    $this->add_output("-------------------");
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
    $model_name = ucfirst(WXActiveRecord::camelize($argv[1]));
    if(!class_exists($model_name) || !$model = new $model_name) {
      exit("[ERROR] Cannot find class name $model_name"."\n");
    }
    $commands = array($argv[1], $argv[2]);
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