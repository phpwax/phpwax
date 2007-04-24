<?php
/**
  *  @package PHP-Wax
  *  Migration Class maintains database structure according to a 'version control methodology'
  *  Ideal for ensuring that database structures are maintained across multiple servers.
  *
  */
  
class WXMigrate
{
  protected $pdo;
  protected $version;
  protected $migration_dir;
  protected $migrations_array = array();
  protected $columns_array = array();
  public $quiet_mode = false;
  
  public function __construct($quiet = false) {
    if($quiet) $this->quiet_mode = true;
    $this->pdo = WXActiveRecord::getDefaultPDO();
    if(!$this->check_schema()) {
      $this->create_schema();
    }
  }
  
  public function check_schema() {
    $sth = $this->pdo->query("show tables");
    while($table = $sth->fetch()) {
      if($table[0] == "migration_info") {
        return true;
      }
    }
    return false;
  }
  
  protected function create_schema() {
    $this->pdo->query("CREATE TABLE `migration_info` (`version` INT(7) unsigned NOT NULL default '0', 
                      `version_latest` INT(7) unsigned NOT NULL default '0', PRIMARY KEY  (`version`))");
    $this->pdo->query("INSERT INTO `migration_info` (`version`, `version_latest`) VALUES (0,0)");    
  }
  
  public function get_version() {
    $row = $this->pdo->query("SELECT version FROM migration_info")->fetch();
    return $row['version'];
  }
  
  public function set_version($version) {
    $this->pdo->query("UPDATE migration_info SET version=".$version);
  }
  
  public function increase_version() {
    $version = $this->get_version() + 1;
    $this->pdo->query("UPDATE migration_info SET version=".$version);
    return $version;
  }
  
  public function decrease_version() {
    if($this->get_version()>0) {
      $version = $this->get_version() - 1;
      $this->pdo->query("UPDATE migration_info SET version=".$version);
    } else {
      $version = 0;
    }
    return $version;
  }
  
  public function get_version_latest() {
    $row = $this->pdo->query("SELECT version_latest FROM migration_info")->fetch();
    return $row['version_latest'];
  }
  
  public function increase_version_latest() {
    $latest_ver = $this->get_version_latest() + 1;
    return $this->pdo->query("UPDATE migration_info SET version_latest=".$latest_ver);
  }
  
  public function create_migration($name) {
    $latest_ver = $this->get_version_latest() + 1;
    $this->pdo->query("UPDATE migration_info SET version_latest=".$latest_ver);
    $name = ucfirst(WXActiveRecord::camelize($name));
    $text = "<?php".                          "\n";
    $text.= "class {$name} extends WXMigrate"."\n";
    $text.= "{".                              "\n";
    $text.= "  public function up() {".       "\n";
    $text.=                                   "\n";
    $text.= "  }".                            "\n";
    $text.=                                   "\n";
    $text.= "  public function down() {".     "\n";
    $text.=                                   "\n";
    $text.= "  }".                            "\n";
    $text.= "}".                              "\n";
    $text.= "?>".                              "\n";
    return $text;
  }
  
  protected function get_version_from_file($file) {
    return ltrim(substr($file, 0 , strpos($file, "_")), "0");
  }
  
  protected function get_class_from_file($file, $strip = true) {
    if($strip) $file = substr($file,3);
    return WXInflections::camelize(str_replace(".php", "", $file), true);
  }
  
  protected function create_migration_array($directory) {   
    $migrations=File::scandir_recursive($directory);
    foreach($migrations as $migration) {
      $version_number_of_file = $this->get_version_from_file($migration);
      $class_name = $this->get_class_from_file($migration);
      $this->migrations_array[$version_number_of_file] = array("file"=>$migration, "class"=>$class_name, "version"=>$version_number_of_file);
    }
  }
  
  protected function get_highest_version() {
    ksort($this->migrations_array);
    $high =  end($this->migrations_array);
    return $high['version'];
  }
  
  public function migrate_revert($directory) {
    $this->create_migration_array($directory);
    krsort($this->migrations_array);
    foreach($this->migrations_array as $migration) {
      if($migration['version'] <= $this->get_version()) {
        include_once($directory.$migration['file']);
        $this->migrate_down(new $migration['class'], $migration['version']);
      }
    }
    $this->set_version("0");
    return "0";
  }

  public function version_less_migrate($directory, $direction="up", $quiet=false) {
    if(!is_readable($directory)) return "Invalid directory";
    $migrations=File::scandir_recursive($directory);
    if(count($migrations)<1) return "No migrations in supplied directory";
    foreach($migrations as $migration) {
      if(!strpos($migration, ".php")) return "Only directories of PHP migration files are allowed"; 
      include_once($directory.$migration);
      $class = $this->get_class_from_file($migration, false);
      $instance = new $class($quiet);
      $instance->{$direction}();
    }
    return "Database setup completed";
  }
  
  public function migrate($directory, $target_version=false) {
    $this->create_migration_array($directory);
    if($target_version===false) {
      $target_version = $this->get_highest_version();
    }
    if($target_version==$this->get_version()) {
      return false;
    }  
    if(count($this->migrations_array)<1) {
      return false;
    }
    if($target_version > $this->get_highest_version() || !array_key_exists($target_version, $this->migrations_array)) {
      $this->output( "...version given does not exist."."\n");
      return false;
    }
    
    $this->output( "...current version: ".$this->get_version()."\n"."...now moving to version: ".$target_version."\n");
    if($target_version < $this->get_version()) {
      $direction = "down";
      krsort($this->migrations_array);
    } else {
      ksort($this->migrations_array);
      $direction = "up";
    }
    if($direction == "down") {
      foreach($this->migrations_array as $migration) {
        include_once($directory.$migration['file']);
        if($migration['version'] == $target_version) {
          $running_version = $migration['version'];
        }
        if($migration['version'] > $target_version && $migration['version'] <= $this->get_version()) {
          $this->migrate_down(new $migration['class'], $migration['version']);
          $running_version = $migration['version'];
        }
      }
    } else {
      foreach($this->migrations_array as $migration) {
        include_once($directory.$migration['file']);
        if($migration['version'] == $target_version) {
          $running_version = $migration['version'];
        }
        if($migration['version'] > $this->get_version() && $migration['version'] <= $target_version) {
          $this->migrate_up(new $migration['class'], $migration['version']);
          $running_version = $migration['version'];
        }
      }
    }
    $this->set_version($running_version);
    return $running_version;
  }
  
  private function migrate_down(WXMigrate $class, $version) {
    $this->output( "...reverting with version ".$version."\n");
    $class->down();
    $this->set_version($version);
    return true;
  }
  
  private function migrate_up(WXMigrate $class, $version) {
    $this->output( "...updating with version ".$version."\n");
    $class->up();
    $this->set_version($version);
    return true;
  }
  
  private function build_column_sql($column) {
    $sql = "`".$column[0]."` ";
    switch($column[1]) {
      case "string": $sql.= "VARCHAR "; break;
      case "integer": $sql.= "INT "; break;
      case "text": $sql.= "TEXT "; $column[2]=null; break;
      default: $sql.= $column[1]." "; $column[2]=null;
    }
    if($column[2]) {
      $sql.= "({$column[2]}) ";
    }
    if($column[3]) {
      $sql.= "NULL ";
    } else {
      $sql.= "NOT NULL ";
    }
    if(isset($column[4])) {
      $sql.= "DEFAULT '".$column[4]."' ";
    }
    return $sql;
  }
  
  public function create_table($table_name, $id=true) {
    try {
      $sql = "CREATE TABLE IF NOT EXISTS `$table_name`(";
      if($id) $sql.= "`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY";
      if(count($this->columns_array) > 0) {
        if($id) $sql.= ", ";
        foreach($this->columns_array as $column) {
          $sql.= $this->build_column_sql($column);
          $sql.= ",";
        }
      }
      $this->columns_array = array();
      $sql = rtrim($sql, ",");
      $sql.= ")";
      $this->pdo->query($sql);
      $this->output( "...created table $table_name"."\n");
			return true;
    } catch(Exception $e) {
      $e = false; return $e;
    }
  }
  
  public function drop_table($table_name) {
    $sql = "DROP TABLE IF EXISTS `$table_name`";
    $this->pdo->query($sql);
    $this->output( "...removed table $table_name"."\n" );
  }
  
  public function create_column($name, $type="string", $length = "128", $null=true, $default=null) {
    $this->columns_array[] = array($name, $type, $length, $null, $default);
  }
  
  public function add_column($table, $name, $type="string", $length = "128", $null=true, $default=null) {
    try {
      if($type=="integer" && $length>11) $length="11";
      $column = array($name, $type, $length, $null, $default);
      $sql = "ALTER TABLE `$table` ADD ";
      $sql.= $this->build_column_sql($column);
      $this->pdo->query($sql);
      $this->output( "...added column $name to $table"."\n" );
			return true;
    } catch(Exception $e) {
      $this->catcher($e);
    }
  }
  
  public function remove_column($table, $name) {
    try {
      $sql = "ALTER TABLE `$table` DROP `$name`";
      $this->pdo->query($sql);
      $this->output( "...removed column $name from $table"."\n" );
    } catch(Exception $e) {
      $this->catcher($e);
    }
  }
  
  public function change_column($table, $name, $type="string", $length = "128", $null=true, $default=null) {
    try {
      $column = array($name, $type, $length, $null, $default);
      $sql = "ALTER TABLE `$table` CHANGE `$name` ";
      $sql.= $this->build_column_sql($column);
      $this->pdo->query($sql);
      $this->output( "...changed column $name in $table"."\n" );
    } catch(Exception $e) {
      $this->catcher($e);
    }
  }
  
  public function rename_table($table, $new_name) {
    try {
      $sql = "ALTER TABLE `$table` RENAME `$new_name`";
      $this->pdo->query($sql);
      $this->output( "...renamed table $table to $new_name"."\n");
    } catch(Exception $e) {
      $this->catcher($e);
    }
  }
  
  public function run_sql($sql) {
    try {
      $this->pdo->query($sql);
      $this->output( "...executed raw sql command"."\n");
    } catch(Exception $e) {
      $this->catcher($e);
    }
  }
  
  protected function output($string) {
    if(!$this->quiet_mode) {
      echo $string;
    }
  }
  
  protected function catcher($e) {
    $this->output( "Notice: error with query: {$e->getMessage()}"."\n" );
    return false;
  }
  
  public function up() {}
  public function down() {}
  
}


?>