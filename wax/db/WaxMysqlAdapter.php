<?php
/**
 * Mysql Adapter class
 *
 * @package PhpWax
 **/
class  WaxMysqlAdapter extends WaxDbAdapter {
  
  
  
  public function build_query($params) {
 		if( $params['distinct'] ) {
 			$sql = "SELECT DISTINCT {$params['distinct']} FROM `{$this->table}`";
 		} elseif( $params['columns'] ) {
     	$sql = "SELECT {$params['columns']} FROM `{$this->table}`";
     } else {
       $sql = "SELECT * FROM `{$this->table}`";
     }
     if($params['table']) $sql.=", ".$params['table'];

     if(!empty($params['join'])) {
       $join = $params['join'];
       if (count($join) && $join['table'] && $join['lhs'] && $join['rhs']) {
     	  $sql .= " INNER JOIN `{$join['table']}`".
       	  			" ON `{$this->table}`.{$join['lhs']}=`{$join['table']}`.{$join['rhs']}";
       }
     }
     $where = false;
     if( count( $this->constraints ) ) {
     	$sql .= ' WHERE ' . $this->_makeANDConstraints( $this->constraints );
       $where = true;
     }

     if(!$params['find_id']) {
   		if($params['conditions']) {
       	if( $where ) {
         	$sql .= " AND ({$params['conditions']})";
         } else {
           $sql .= " WHERE {$params['conditions']}";
           $where = true;
         }
       }
       if($params['group']) {
       	$sql .= " GROUP BY {$params['group']}";
       }
   		if($params['order']) {
       	$sql .= " ORDER BY {$params['order']}";
       }


   		if( $params['direction'] ) {
       	$sql .= " {$params['direction']}";
       }

       if($params['limit']) {
       	$limit = intval( $params['limit'] );		
       	if($params['offset']) {
         	$offset = intval( $params['offset'] );
         	$sql .= " LIMIT {$offset}, {$limit} ";
         } else {
           $sql .= " LIMIT {$limit}";
         }
   		}
 		} else {
 			if( $where ) {
       	$sql .= " AND (`id`={$params['find_id']})";
       } else {
         $sql .= " WHERE `id`={$params['find_id']}";
         $where = true;
       }
 		}

 		if( $params["sql"]) {
 			$sql=$params["sql"];
 		}
 		$sql .= ';';
 		return $sql;
 	}
 	
 	function _makeANDConstraints( $array ) {
     foreach( $array as $key=>$value ) {
       if(is_null( $value ) ) {
         $expressions[] = "`{$this->table}`.{$key} IS NULL";
       } else {
         $expressions[] = "`{$this->table}`.{$key}=:{$key}";
       }
     }
     return implode( ' AND ', $expressions );
   }

   function _makeUPDATEValues( $array ) {
     foreach( $array as $key=>$value ) {
       $expressions[] ="`{$key}`=:{$key}";
     }
     return implode( ', ', $expressions );
   }

   function _makeBindingParams( $array ) {
 		$params = array();
 		foreach( $array as $key=>$value ) {
 			$params[":{$key}"] = $value;
 		}
     return $params;
   }
   
   function _makeIDList( $array ) {
    	$expressions = array();
      foreach ($array as $id) {
        $expressions[] = "`{$this->table}`.id=".
        $this->pdo->quote($id, isset($this->has_string_id) ? PDO_PARAM_INT : PDO_PARAM_STR);
      }
      return '('.implode(' OR ', $expressions).')';
    }
  
} // END class 