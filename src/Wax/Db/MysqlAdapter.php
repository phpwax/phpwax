<?php
namespace Wax\Db;

/**
 * Mysql Adapter class
 *
 * @package PhpWax
 **/
class  MysqlAdapter extends Adapter {
  protected $date = 'CURDATE()';
	protected $timestamp = 'NOW()';  
}