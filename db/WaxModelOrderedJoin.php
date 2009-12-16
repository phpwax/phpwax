<?php
/**
* Same as a Join, but with order
*/
class WaxModelOrderedJoin extends WaxModelJoin {
  public function setup() {
    parent::setup();
    $this->define("join_order", "IntegerField");
  }
}

