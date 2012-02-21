<?php
namespace Wax\Db\Query;


abstract class Query {

  
  public function update() {}
  public function insert() {}
  public function select() {}
  public function delete() {}
  public function replace() {}


  public function bindings($keys, $value=FALSE) {}
  public function values($values) {}

}