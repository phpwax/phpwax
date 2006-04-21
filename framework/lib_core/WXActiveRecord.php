<?php

/*
 * @package wx.php.core
 *
 * This class is based in part on CBL ActiveRecord. 
 * For more information, see:
 *  http://31tools.com/cbl_activerecord/
 */

/**
 *  exception class for WXActiveRecord
 */
class WXActiveRecordException extends Exception
{
    function __construct( $message, $code )
    {
        return parent::__construct( $message, $code );
    }
}

// exception code
define( 'ar_construct_by_id',           0 );
define( 'ar_construct_by_constraints',  1 );

if( ! is_numeric( @PDO_FETCH_ASSOC ) )
{
    define( 'PDO_FETCH_ASSOC', PDO::FETCH_ASSOC );
    define( 'PDO_PARAM_INT', PDO::PARAM_INT );
    define( 'PDO_PARAM_STR', PDO::PARAM_STR );
}

/**
 *  active record
 *  @package wx.php.core
 */
class WXActiveRecord
{
    protected static $default_pdo = null;

    /**
     *  set default PDO instnace
     *  @param  object  pdo     PDO instance
     */
    static function setDefaultPDO( $pdo )
    {
        self::$default_pdo = $pdo;
    }

    /**
     *  get default PDO instance
     *  @return object      PDO instance
     */
    static function &getDefaultPDO()
    {
        return self::$default_pdo;
    }

    protected $pdo = null;
    protected $table = null;
    protected $row = array();
    protected $constraints = array();
    protected $children = array();

    /**
     *  constructor
     *  @param  mixed   param   PDO instance,
     *                          or record id (if integer),
     *                          or constraints (if array) but param['pdo'] is PDO instance
     */
    function __construct( $param = null )
    {
        $this->pdo = self::$default_pdo;
        $class_name =  get_class($this) ;
        if( $class_name != 'wxactiverecord' )
        {
            $this->table = $this->underscore( $class_name );
        }

        if( ! $param ) return;

        if( strtolower( get_class( $param ) ) == 'pdo' )
        {
            // parameter is PDO instance
            $this->pdo = $param;
        }
        else if( is_numeric( $param ) || is_string( $param ) )
        {
            // parameter is numeric, then treat as 'id'
            if( ! $this->_find( $param ) )
            {
                throw new WXActiveRecordException(
                    'Fail to construct by record id.',
                    ar_construct_by_id );
            }
        }
        else if( is_array( $param ) )
        {
            // parameter is array

            if( @$param['pdo'] &&
                strtolower( get_class( $param['pdo'] ) ) == 'pdo' )
            {
                $this->pdo = $param['pdo'];
                unset( $param['pdo'] );
            }

            if( count( $param ) )
            {
                $this->constraints = $param;
                if( ! $this->_find() )
                {
                    throw new WXActiveRecordException(
                        'Fail to construct by constraints.',
                        ar_construct_by_constraints );
                }
            }
        }
    }

    /**
     * get PDO instance
     */
    function getPDO()
    {
        return $this->pdo;
    }

    /**
     *  get property
     *  @param  string  name    property name
     *  @return mixed           property value
     */
    public function __get( $name )
    {
        assert( is_array( $this->row ) );
        if( array_key_exists( $name, $this->row ) )
        {
            // record column value
            return $this->row[$name];
        }

        // get child object
        //$id = intval( $this->row['id'] );
        $id = @$this->row['id']; // modify at 2005/12/14 for string id
        if( $id )
        {
            $foreign_key = $this->table . '_id';

            assert( is_array( $this->children ) );
            if( array_key_exists( $name, $this->children ) &&
                $this->children[$name]->getConstraint( $foreign_key ) == $id )
            {
                // return cashed instance
                return $this->children[$name];
            }

            $class_name = ucfirst( $name );
            if( class_exists( $class_name, FALSE ) )
            {
                // create instance
                $child = new $class_name( $this->pdo );
                $child->setConstraint( $foreign_key, $id );
                $this->children[$name] = $child;
                return $child;
            }
        }

        return null;
    }

    /**
     *  set property
     *  @param  string  name    property name
     *  @param  mixed   value   property value
     */
    public function __set( $name, $value )
    {
        if( ! is_array( $this->row ) )
        {
            $this->row = array();
        }
        $this->row[$name] = $value;
    }

    /**
     *  set constraints
     *  @param  string  name    column name
     *  @param  mixed   value   column value
     */
    function setConstraint( $name, $value )
    {
        $this->constraints[$name] = $value;
    }

    /**
     *  get constraints
     *  @param  string  name    column name
     *  @return mixed           column value
     */
    function getConstraint( $name )
    {
        return array_key_exists( $name, $this->constraints) ? $this->constraints[$name] : null;
    }

    /**
     *  get one record
     *  @param  mixed id            record id
     *  @return WXActiveRecord    this instance, or null if failed
     */
    function find( $id = null, $params = null )
    {
        $record = clone( $this );
        return $record->_find( $id, $params ) ? $record : null;
    }

    /**
     *  get one record helper
     *  @param  mixed id            record id
     *  @return boolean
     */
    protected function _find( $id = null, $params = null )
    {
        if( is_null( $params ) )
        {
            $params = array();
        }

        if( $id )
        {
            if( is_numeric( $id ) && ! isset( $this->has_string_id ) )
            {
                $id = intval( $id );
            }

            $this->constraints['id'] = $id;
        }

        if( @$params['columns'] )
        {
            $sql = "SELECT {$params['columns']} FROM `{$this->table}`";
        }
        else
        {
            $sql = "SELECT * FROM `{$this->table}`";
        }

        if( count( $this->constraints ) )
        {
            $sql .= ' WHERE ' . $this->_makeANDConstraints( $this->constraints );
            $binding_params = $this->_makeBindingParams( $this->constraints );
        }
        $sql .= ';';

        $sth = $this->pdo->prepare( $sql );
        if( ! $sth )
        {
            $err = $this->pdo->errorInfo();
            trigger_error( "{$err[2]}:{$sql}", E_USER_ERROR );
        }
        if( ! $sth->execute( @$binding_params ) )
        {
            $err = $sth->errorInfo();
            trigger_error( "{$err[2]}:{$sql}", E_USER_ERROR );
        }

        $row = $sth->fetch( PDO_FETCH_ASSOC );
        $sth->closeCursor();
        if( ! $row ) return FALSE;

        $this->row = $row;
        return TRUE;
    }

    /**
     *  get record list
     *  @param  array   params  option array
     *                          params['conditions'] : WHERE phrase in SQL
     *                          params['order'] : ORDER phrase in SQL
     *  @return array           array of ActiveRecord Objects
     */
    function find_all( $params = null, $join = null )
    {
        if (! is_array($params)) $params = array();
        if (! is_array($join)) $join = array();
				
				if( @$params['distinct'] ) {
					$sql = "SELECT DISTINCT {$params['distinct']} FROM `{$this->table}`";
				} 
        if( @$params['columns'] )
        {
            $sql = "SELECT {$params['columns']} FROM `{$this->table}`";
        }
        else
        {
            $sql = "SELECT * FROM `{$this->table}`";
        }

        if (count($join) && @$join['table'] && @$join['lhs'] && @$join['rhs']) {
            $sql .= " INNER JOIN `{$join['table']}`".
                " ON `{$this->table}`.{$join['lhs']}=`{$join['table']}`.{$join['rhs']}";
        }

        $where = FALSE;
        if( count( $this->constraints ) )
        {
            $sql .= ' WHERE ' . $this->_makeANDConstraints( $this->constraints );
            $where = TRUE;
        }

        if( @$params['conditions'] )
        {
            if( $where )
            {
                $sql .= " AND ({$params['conditions']})";
            }
            else
            {
                $sql .= " WHERE {$params['conditions']}";
                $where = TRUE;
            }
        }

        if( @$params['order'] )
        {
            $sql .= " ORDER BY {$params['order']}";
        }
				if( @$params['direction'] )
        {
            $sql .= " {$params['direction']}";
        }

        if( array_key_exists( 'limit', $params ) )
        {
            $limit = intval( $params['limit'] );
            if( array_key_exists( 'offset', $params ) )
            {
                $offset = intval( $params['offset'] );
                $sql .= " LIMIT {$limit} OFFSET {$offset}";
            }
            else
            {
                $sql .= " LIMIT {$limit}";
            }
        }

				if( @$params["sql"]) {
					$sql=$params["sql"];
				}

        $sql .= ';';
        $binding_params = $this->_makeBindingParams( $this->constraints );
        $sth = $this->pdo->prepare( $sql );
        if( ! $sth )
        {
            $err = $this->pdo->errorInfo();
            trigger_error( "{$err[2]}:{$sql}", E_USER_ERROR );
        }
        if( ! $sth->execute( $binding_params ) )
        {
            $err = $sth->errorInfo();
            trigger_error( "{$err[2]}:{$sql}", E_USER_ERROR );
        }

        $row_list = $sth->fetchAll( PDO_FETCH_ASSOC );
        $item_list = array();
        foreach( $row_list as $row )
        {
						$newtable=$this->camelize($this->table);
            $item = new $newtable( $this->pdo );
            $item->row = $row;
            $item->constraints = $this->constraints;
            if (isset($row['id'])) {
                $item_list[$row['id']] = $item;
            } else {
                $item_list[] = $item;
            }
        }

        return $item_list;
    }

		/**
		 *  Alias function to maintain BC
		 */
		function findAll($params = null, $join = null) {
			return $this->find_all($params, $join);
		}
		
		function findBySql($sql) {
			return $this->find_all( array("sql"=>$sql) );
		}

    /**
     *  insert record to table, or update record data
     */
    function save()
    {
    	if( @$this->row['id'] ) {
      	return $this->update();
      }else{
      	unset( $this->row['id'] );
        return $this->insert();
      }
    }

    /**
     *  delete record from table
     *  @param  mixed id    record id
     *  @return boolean
     */
    function delete( $id )
    {
        if( is_numeric( $id ) && ! isset( $this->has_string_id ) )
        {
            $id = intval( $id );
        }
        $this->constraints['id'] = $id;
        $sql = "DELETE FROM `{$this->table}` WHERE " .
            $this->_makeANDConstraints( $this->constraints ) . ';';
        $binding_params = $this->_makeBindingParams( $this->constraints );

        $sth = $this->pdo->prepare( $sql );
        if( ! $sth )
        {
            $err = $this->pdo->errorInfo();
            trigger_error( "{$err[2]}:{$sql}", E_USER_ERROR );
        }
        if( ! $sth->execute( $binding_params ) )
        {
            $err = $sth->errorInfo();
            trigger_error( "{$err[2]}:{$sql}", E_USER_ERROR );
        }

        $this->row = array();
        return $sth->rowCount() > 0;
    }

    function count($params = null)
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if (isset($params['conditions']) && $params['conditions'] != '')
        {
            $sql .= " WHERE {$params['conditions']}";
        }
        $sql .= ';';
        $sth = $this->pdo->query( $sql );
        return intval( $sth->fetchColumn() );
    }
/*
    function _update()
    {
        $values = $this->row;
        unset( $values['id'] );
        if( ! count( $values ) )
        {
            trigger_error( 'No record value.', E_USER_ERROR );
        }
        $sql = "UPDATE `{$this->table}` SET " .
            $this->_makeUPDATEValues( $values ) .
            " WHERE `{$this->table}`.id=:id;";
        $binding_params = $this->_makeBindingParams( $this->row );

        $sth = $this->pdo->prepare( $sql );
        if( ! $sth )
        {
            $err = $this->pdo->errorInfo();
            trigger_error( "{$err[2]}:{$sql}", E_USER_ERROR );
        }
        if( ! $sth->execute( $binding_params ) )
        {
            $err = $sth->errorInfo();
            trigger_error( "{$err[2]}:{$sql}", E_USER_ERROR );
        }

        return $sth->rowCount() > 0;
    }
*/
    function update( $id_list = array() )
    {
        $values = $this->row;
        unset($values['id']);
        if (! count( $values)) {
            trigger_error( 'No record value.', E_USER_ERROR );
        }

        $sql = "UPDATE `{$this->table}` SET ".
            $this->_makeUPDATEValues($values);

        if (isset($this->row['id']) && $this->row['id']) {
            $sql .= " WHERE `{$this->table}`.id=:id;";
        } else if (count($id_list)) {
            $sql .= ' WHERE '.$this->_makeIDList($id_list).';';
        } else {
            trigger_error('ID is not specified.', E_USER_ERROR);
        }
        $binding_params = $this->_makeBindingParams($this->row);

        $sth = $this->pdo->prepare($sql);
        if (! $sth) {
            $err = $this->pdo->errorInfo();
            trigger_error("{$err[2]}:{$sql}", E_USER_ERROR);
        }
        if (! $sth->execute($binding_params)) {
            $err = $sth->errorInfo();
            trigger_error("{$err[2]}:{$sql}", E_USER_ERROR);
        }

        return $sth->rowCount();
    }

    function insert()
    {
        $this->row = array_merge( $this->constraints, $this->row );
        $binding_params = $this->_makeBindingParams( $this->row );
        $sql = "INSERT INTO `{$this->table}` (" .
            implode( ', ', array_keys($this->row) ) . ') VALUES(' .
            implode( ', ', array_keys($binding_params) ) . ');';

        $sth = $this->pdo->prepare( $sql );
        if( ! $sth )
        {
            $err = $this->pdo->errorInfo();
            trigger_error( "{$err[2]}:{$sql}", E_USER_ERROR );
        }
        if( ! $sth->execute( $binding_params ) )
        {
            $err = $sth->errorInfo();
            trigger_error( "{$err[2]}:{$sql}", E_USER_ERROR );
        }

        if( ! @$this->row['id'] && ! isset( $this->has_string_id ) )
        {
            $this->row['id'] = $this->pdo->lastInsertId();
            return intval( $this->row['id'] );
        }

        return @$this->row['id'];
    }

    function uniqid($len = 8, $set = TRUE)
    {
        if ($len < 8) {
            trigger_error('ID length is short.', E_USER_ERROR);
        }
        $sql = "SELECT id FROM `{$this->table}` WHERE id=:id;";
        $sth = $this->pdo->prepare($sql);
        do {
            $id = substr(md5(uniqid()), 0, $len);
            $sth->execute(array('id'=>$id));
            $row = $sth->fetch();
            $sth->closeCursor();
        } while ($row);
        if ($set) {
            $this->id = $id;
        }
        return $id;
    }

    function _makeANDConstraints( $array )
    {
        foreach( $array as $key=>$value )
        {
            if( is_null( $value ) )
            {
                $expressions[] = "`{$this->table}`.{$key} IS NULL";
            }
            else
            {
                $expressions[] = "`{$this->table}`.{$key}=:{$key}";
            }
        }
        return implode( ' AND ', $expressions );
    }

    function _makeUPDATEValues( $array )
    {
        foreach( $array as $key=>$value )
        {
            $expressions[] ="{$key}=:{$key}";
        }
        return implode( ', ', $expressions );
    }

    function _makeBindingParams( $array )
    {
        $params = array();
        foreach( $array as $key=>$value )
        {
            $params[":{$key}"] = $value;
        }
        return $params;
    }

    function _makeIDList( $array )
    {
        $expressions = array();
        foreach ($array as $id) {
            $expressions[] = "`{$this->table}`.id=".
                $this->pdo->quote($id, isset($this->has_string_id) ? PDO_PARAM_INT : PDO_PARAM_STR);
        }
        return '('.implode(' OR ', $expressions).')';
    }

		/**
     * Convert underscore_words to camelCaps.
     */
    public function camelize($name)
    {
        // lowercase all, underscores to spaces, and prefix with underscore.
        // (the prefix is to keep the first letter from getting uppercased
        // in the next statement.)
        $name = '_' . str_replace('_', ' ', strtolower($name));

        // uppercase words, collapse spaces, and drop initial underscore
        return ltrim(str_replace(' ', '', ucwords($name)), '_');
    }


    /**
     * Convert camelCaps to underscore_words.
     */
    public function underscore($name)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1_$2", $name));
    }

		public function add_row_save($array) {
			foreach($array as $k=>$v) {
			  $this->$k=$v;
			}
			return $this->save();
		}

		public function __call( $func, $args ) {
			$what=substr( $func, 6 );
			$what=explode("And", $what);
			for($i=0;$i<count($what); $i++) {
				$what[$i]=$this->underscore($what[$i]);
			}			
      if( $args ) {
				if(count($what)>1 && count($args)>1) { 
					$conds=$what[0]."='".$args[0]."' AND ".$what[1]."='".$args[1]."'";
				}else{
					$conds=$what[0]."='".$args[0]."'";
				}
				$params = array("conditions"=>$conds);
        return $this->find_all($params);
      }
    }

}

class WXActiveRoot extends WXActiveRecord
{
    public function __get( $name )
    {
        assert( is_array( $this->children ) );
        if( array_key_exists( $name, $this->children ) )
        {
            return $this->children[$name];
        }

        $class_name = ucfirst( $name );
        if( class_exists( $class_name, FALSE ) )
        {
            // create instance
            $child = new $class_name( $this->pdo );
            $this->children[$name] = $child;
            return $child;
        }

        return null;
    }
}
?>