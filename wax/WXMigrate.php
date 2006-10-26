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
  
  protected function get_version() {
    $row = $this->pdo->query("SELECT version FROM migration_info")->fetch();
    return $row['version'];
  }
  
  protected function set_version($version) {
    if($version > 0) {
      return false;
    }
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
  
  public function migrate($directory, $version=false) {
    $files_to_migrate = array();
    if(!$version || $version==$this->get_version()) {
      $version = $this->get_version()+1;
    }
    $migrations=scandir($directory);
    foreach($migrations as $migration) {
      $file_version = substr($migration, 0 , strpos($migration, "_"));
      $class_name = ucfirst(WXActiveRecord::camelize(ltrim(strstr($migration, "_"),"_")));
      if(ltrim($file_version, '0') >= $version) {
        $files_to_migrate[$migration]=rtrim($class_name, ".php");
      }
    }
    if(count($files_to_migrate)<1) {
      return false;
    }

    if($version < $this->get_version()) {
      krsort($files_to_migrate);
      $direction = "down";
    } else {
      ksort($files_to_migrate);
      $direction = "up";
    }
    if($direction == "down") {
      foreach($files_to_migrate as $file_to_include=>$class_name) {
        $file_version = substr($file_to_include, 0 , strpos($migration, "_"));
        include_once($directory.$file_to_include);
        $this->migrate_down(new $class_name, $file_version);
      }
    } else {
      foreach($files_to_migrate as $file_to_include=>$class_name) {
        $file_version = substr($file_to_include, 0 , strpos($migration, "_"));
        include_once($directory.$file_to_include);
        $this->migrate_up(new $class_name, $file_version);
      }
    }
    return true;
  }
  
  protected function migrate_down(WXMigrate $class, $version) {
    $class->down();
    echo "Reverted to version ".$version."\n";
    $this->set_version($version);
    return true;
  }
  
  protected function migrate_up(WXMigrate $class, $version) {
    $class->up();
    echo "Updated to version ".$version."\n";
    $this->set_version($version);
    return true;
  }
  
  protected function create_table($table_name, $columns=null) {
    $sql = "CREATE TABLE `$table_name`(";
    $sql.= "`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY";
    if($columns) {
      $sql.= $this->build_columns($columns);
    }
    $sql.= ")";
    $this->pdo->query($sql);
    echo "...created table $table_name"."\n";
  }
  
  protected function drop_table($table_name) {
    $sql = "DROP TABLE `$table_name`";
    $this->pdo->query($sql);
    echo "...removed table $table_name"."\n";
  }
  
  public function up() {}
  public function down() {}
  
}


?>