<?php
class WaxNoneAdapter extends WaxDbAdapter{
  public function search(WaxModel $model, $text, $columns=array()) { return $model; }
  public function delete(WaxModel $model) { return $model; }
  public function random() { return "RAND()"; }
  public function update(WaxModel $model) { return $model; }
  public function insert(WaxModel $model) { return $model; }
  public function syncdb(WaxModel $model) { return "no sync necessary, this is the none adapter, think /dev/null"; }
  public function query($sql) { return false; }
  public function select(WaxModel $model) { return array(); }
  public function drop_table($table_name) { return "no tables exist, this is the none adapter, think /dev/null"; }
}?>