<?php
/**
  *  @package wax.php
  *  Migration Class
  *
  */
  
class WXMigrate
{
  protected $pdo;
  protected $version;
  protected $migration_dir;
  protected $migrations_array = array();
  protected $column_array = array();
  
  public function __construct() {
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
  
  protected function set_version($version) {
    $this->pdo->query("UPDATE migration_info SET version=".$version);
  }
  
  protected function increase_version() {
    $version = $this->get_version() + 1;
    $this->pdo->query("UPDATE migration_info SET version=".$version);
    return $version;
  }
  
  protected function decrease_version() {
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
  
  protected function get_class_from_file($file) {
    return rtrim(ucfirst(WXActiveRecord::camelize(ltrim(strstr($file, "_"),"_"))), ".php" );
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
    foreach($this->migrations_array as $migration) {
      if($migration['version'] < $this->get_version()) {
        include_once($directory.$migration['file']);
        $this->migrate_down(new $migration['class'], $migration['version']);
      }
    }
    $this->set_version("0");
    return "0";
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
      echo "...version given does not exist."."\n";
      return false;
    }
    
    echo "...current version: ".$this->get_version()."\n"."...now moving to version: ".$target_version."\n";
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
    echo "...reverting with version ".$version."\n";
    $class->down();
    $this->set_version($version);
    return true;
  }
  
  private function migrate_up(WXMigrate $class, $version) {
    echo "...updating with version ".$version."\n";
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
      $sql.= "NOT NULL";
    }
    if($column[4]) {
      $sql.= "DEFAULT '".$column[4]."' ";
    }
    return $sql;
  }
  
  protected function create_table($table_name) {
    $sql = "CREATE TABLE `$table_name`(";
    $sql.= "`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY";
    if(count($this->columns_array) > 0) {
      $sql.= ", ";
      echo $sql; exit;
      foreach($this->columns_array as $column) {
        $sql.= $this->build_column_sql($column);
        $sql.= ",";
      }
    }
    $this->columns_array = array();
    $sql.= rtrim($sql, ",");
    $sql.= ")";
    echo $sql; exit;
    $this->pdo->query($sql);
    echo "...created table $table_name"."\n";
  }
  
  protected function drop_table($table_name) {
    $sql = "DROP TABLE `$table_name`";
    $this->pdo->query($sql);
    echo "...removed table $table_name"."\n";
  }
  
  protected function create_column($name, $type="string", $length = "128", $null=true, $default=null) {
    $this->columns_array[] = array($name, $type, $length, $null, $default);
  }
  
  public function up() {}
  public function down() {}
  
}


?>