<?php
namespace Wax\Model;

/**
* Same as a Join, but with order
*/
class OrderedJoin extends Join {
  public function setup() {
    parent::setup();
    $this->define("join_order", "IntegerField");
  }
}

