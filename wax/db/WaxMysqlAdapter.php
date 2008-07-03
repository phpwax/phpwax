<?php
/**
 * Mysql Adapter class
 *
 * @package PhpWax
 **/
class  WaxMysqlAdapter extends WaxDbAdapter {
  protected $date = 'CURDATE()';
	protected $timestamp = 'NOW()';  
} // END class 