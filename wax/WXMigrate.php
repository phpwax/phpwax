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
    $this->check_schema();
  }
  
  public function check_schema() {
    $query = $this->pdo->exec("show tables");
    $tables = $query->fetchAll();
  }
  
}


?>