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
      return false;
    }
  }
  
  protected function create_schema() {
    $this->pdo->query("CREATE TABLE `migration_info` (`version` INT(7) unsigned NOT NULL default '0')");
    $this->pdo->query("INSERT INTO `migration_info` (`version`) VALUES (0)");    
  }
  
  protected function get_version() {
    
  }
}


?>