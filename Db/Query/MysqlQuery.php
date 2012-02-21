<?php
namespace Wax\Db\Query;
use Wax\Utilities\Log;


class MysqlQuery extends Query {

	public function insert($model) {
	  return "INSERT into `{$model->table}` (`".join("`,`", array_keys($model->row))."`) 
             VALUES (".join(",", array_keys($this->bindings($model->row))).")";
	}

  public function update($model) {
    if(!$pk = $model->_update_pk) $pk = $model->row[$model->primary_key];
    else {
      $pk = $model->row[$model->primary_key];
      $model->{$model->primary_key} = $model->_update_pk;
    }
    return "UPDATE `{$model->table}` SET ".$this->bindings($model->writable_columns()).
      " WHERE `{$model->table}`.`{$model->primary_key}` = '{$pk}'";
  }
  
  public function group_update($schema, $values, $pks) {
    return "UPDATE `{$schema->table}` SET ".$this->bindings($values).
      " WHERE `{$schema->table}`.`{$schema->primary_key}` IN(".implode(", ",array_fill(0,count($pks),"?")).")";
  }
   
  public function select($model) {
    $sql .= "SELECT ";
    if(is_array($model->_select_columns) && count($model->_select_columns)) $sql.= join(",", $model->_select_columns);
    elseif(is_string($model->_select_columns)) $sql.=$model->_select_columns;
		//mysql extra - if limit then record the number of rows found without limits
		elseif($model->_is_paginated) $sql .= "SQL_CALC_FOUND_ROWS *";
    else $sql.= "*";
    $sql.= " FROM `{$model->table}`";
    
    $filters = $this->filter($model);
    if($filters["sql"]) $sql.= " WHERE ";
    $sql.=$filters["sql"];
    if($params) $params = array_merge($params, $filters["params"]);
    else $params = $filters["params"];
    
    $sql  .= $this->group($model);
    $sql  .= $this->having($model);
    $sql  .= $this->order($model);
    $model->_sql_without_limit = $sql;
    $sql  .= $this->limit($model);
    die($sql);
    return $sql;
  }
  
  public function delete($model) {
    $sql = "DELETE FROM `{$model->table}`";
    if($model->pk()) $sql .= " WHERE {$model->primary_key}={$model->pk()}";
    return $sql;
  }
  
  public function group_delete($schema, $pks) {
    return "DELETE FROM `{$schema->table}` WHERE {$schema->primary_key} IN(".implode(", ",array_fill(0,count($pks),"?")).")";    
  }
  
  
  public function bindings($keys, $by_value=FALSE) {
    foreach($keys as $key) {
      if($by_value) $expressions[] ="`{$key}`=:{$key}";
      else $expressions[] = "`{$key}`=?";
    }
    return join( ', ', $expressions );
  }
  
  
  public function filter($model, $filter_name = "_filters") {
    $params = [];
    $sql = "";
    if(count($model->$filter_name)) {
      foreach($model->$filter_name as $filter) {
        if(is_array($filter)) {
          //add table name if it's a column
          if(in_array($filter["name"], $model->writable_columns())) {
            $sql.= "`$model->table`.";
          } 
          $sql.= $filter["name"].$this->operators[$filter["operator"]].$this->map_operator_value($filter["operator"], $filter["value"]);
          if(is_array($filter["value"])) foreach($filter["value"] as $val) $params[]=$val;
          else $params[]=$filter["value"];
          $sql .= " AND ";
        } else {
          $sql.= $filter." AND ";
        }
      }
    }
    $sql = rtrim($sql, "AND ");
    return ["sql"=>$sql, "params"=>$params];
  }
  
  protected function map_operator_value($operator, $value) {
    switch($operator) {
      case "=": return "?";
      case "!=": return "?";
      case "~": return "%?%";
      case "LIKE": return " LIKE ?";
      case "in": return "(".rtrim(str_repeat("?,", count($value)), ",").")";
      case "raw": return "";
      default: return "?"; 
    }
  }

  public function group($model) {if($model->_group_by) return " GROUP BY {$model->_group_by}"; }
  public function having($model) {if($model->_having) return " HAVING {$model->_having}";  }
  public function order($model) {if($model->_order) return " ORDER BY {$model->_order}";}
  public function limit($model) {if($model->_limit) return " LIMIT {$model->_offset}, {$model->_limit}";}
  
  
  
  
  

}