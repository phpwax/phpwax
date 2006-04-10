<?php
/**
 *  @package wx.php.db
 */
/**
 *  @package wx.php.db
 *  ActiveRecord_Adapter is the plugin to ActiveRecord that
 *  makes ActiveRecord work on different databases when it comes
 *  to database-independent SQL statements.
 *
 *  This class provides the base functionality needed for
 *  the SQL standard. When creating a database specific adapter
 *  there is no need to implement anything unless you really need
 *  to do so because of databse-specific queries ( for now we'll
 *  try to do it proper SQL, let me know if I miss anything )
 *
 *  The adapter is in charge of getting the field information
 *  from the database, you might be using PDO, or implement
 *  it via database specific calls.
 *
 */
abstract class ActiveRecord_Adapter implements IteratorAggregate
{	

    /**
     *  Adapters are only created once per table, the $adapters
     *  variable keeps these stored for easy access via the 
     *  instanceFor() function
     *  @access public 
     *  @var array
     */
    static $adapters = array();
    
    // {{{Êprotected variables
    /**
     *  Holds the table name of the current class.
     *  @access protected
     *  @var string
     */
    protected $table;
    /**
     *  The fields are the field name and field values
     *  from the current table.
     *  @access protected
     *  @var array
     */
    protected $fields;
    // }}}
    
    // {{{Êpublic function __construct( $table )
    /**
     *  Constructor, sets up the default values.
     *
     *  @access public
     *  @return void
     *  @param string The name of the table corresponding to the table used
     */
    public function __construct( $table )
    {
        $this->table = $table;
        $this->fields = array();
    }
    // }}}
    
    // {{{ public function validField( $field )
    /**
     *  Check if a field is valid according to the fields that is in 
     *  the table for this class.
     *
     *  @access public
     *  @return bool If the field is valid or not
     *  @param string The field to check
     */
    public function validField( $field )
    {
        return isset( $this->fields[$field] );
    }
    // }}}
    
    // {{{ public function getField( $field )
    /**
     * 	Retrive the field configuration for a single
     *	table field.
     *
     *	@access public
     *	@return array
     *	@param string The table field
     */
    public function getField( $field )
    {
        return $this->fields[$field];
    }
  	// }}}
	
	// {{{ public function getIterator()
	/**
	 *	Get a iterator for all the fields for the 
	 *	current table.
	 *
	 *	@access public
	 *	@return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator(
			new ArrayObject( $this->fields )
		);
	}
	// }}}
	
    // --- SQL statements --- //
    // {{{ public function sqlBasicSelect( $key, $args, $in )
    /**
     *	Basic SQL select statement
     *
     *	@todo WILL BE REFACTORED!
     *
     *	@access public
     *	@return string SQL statement ready for PDO
     */
	public function sqlBasicSelect( $key, $args = null, $in = '' )
    {
        $sql  = "SELECT * FROM " . $this->table . " WHERE ";
        $order = ' ORDER BY ' . $key;
        $limit = '';
		if( is_array( $args ) )
        {
            foreach( $args as $k => $v )
            {
                switch( $k )
                {
                    case 'order':
                        $order = ' ORDER BY ' . $v;
                        break;
                    case 'direction':
                        $order .= ' ' . $v;
                        break;
                    case 'key':
                        $key = $v;
                        break;
					case 'limit':
						$limit =  ' LIMIT '.$v.$limit;
						break;
					case 'offset':
						$limit = $limit.' OFFSET '.$v;
						break;
                }
            }
        }
        $sql .= " $key ";
        if( is_array( $in) )
        {
            $in = ' IN ( ' . implode( $in, ',' ) . ' )';
            return $sql . $in . $order . $limit;
        }
        return $sql . ' = :' . $key . $order . $limit;
    }
	// }}}
    
    // {{{ public function sqlFindAll( $args = null, $obj )
    /**
     *	Find <em>all</em> entries for a single table.
     *
     *	@access public
     *	@return string The SQL query
     *	@param array SQL modifiers
     */
    public function sqlFindAll( $args = null, ActiveRecord $obj )
    {
    	$primary = $obj->primarykey;
    	$query = "SELECT * FROM " . $this->table;
    	$order = " ORDER BY $primary";
		$limit = '';
    	if( is_array( $args ) )
    	{
			foreach( $args as $k => $v )
			{
				switch( $k )
				{
					case 'order':
						$order = ' ORDER BY ' . $v;
						break;
					case 'limit':
						$limit =  ' LIMIT '.$v.$limit;
						break;
					case 'offset':
						$limit = $limit.' OFFSET '.$v;
						break;
				}
			}
		}
		return $query . $order.$limit;
	}
	// }}}
    
	// {{{ public function sqlCount()
	/**
	 *	Get the row count for the current table 
	 *
	 *	The value is available in the variable "count" in the result.
	 *	If this is sub-classed it needs to put the result in the "count"
	 *	variable as well so that AR can get to it.
	 *
	 *	@access public
	 *	@return string SQL statement ready for PDO
	 */
    public function sqlCount( )
    {
        return 'SELECT count( * ) as count FROM ' . $this->table;
    }
	// }}}
    
    // --- Update/save/delete functions --//
    // {{{ public function saveNew( ActiveRecord $obj )
    /**
     *	Save a new ActiveRecord object
     *
     *	@todo Filter and whatnot
     *
     *	@access public
     *	@return integer If the object was saved: the objects ID, 0 if not
     *	@param ActiveRecord 
     */
	public function saveNew( ActiveRecord $obj )
    {
    	$primary = $obj->primarykey; 
    	$list = array();
    	foreach( $obj as $k => $v )
    	{
    		// We don't want to update the primary key
    		// We only want to update valid fields
    		if( $k != $primary && $this->validField( $k ) )
    		{
    			$list[$k] = $v;
    		}
    	}
    	$query = 'INSERT INTO ' . $this->table . '
    		(' .implode( ', ', array_keys( $list ) ) . ') 
    		VALUES (
    		:' . implode( ', :', array_keys( $list ) ) . '
    		)';
    	$stmt = ActiveRecordPdo::instance()->prepare( $query );
		foreach( $list as $k => $v )
		{
			if( $k != $primary )
			{
				$stmt->bindParam( ':' . $k, $list[$k] );
			}
		}
		if( $stmt->execute() )
		{
			$s = ActiveRecordPdo::instance()->prepare( $this->lastId() );
			if( $s->execute() )
			{
				$row = $s->fetch();
				return $row["id"];
			}
		}
		return 0;
    }
    // }}}
    
    /**
     *	Update a single ActiveRecord object
     *
     *	@todo Filter and whatnot
     *
     *	@access public
     *	@return bool If the ActiveRecord was updated
     */
    public function update( ActiveRecord $obj )
    {
    	$primary = $obj->primarykey;
    	$query = "UPDATE " . $this->table . " ";
    	$query .= "SET ";
    	$list = array();
    	foreach( $obj as $k => $v  )
    	{
    		if( $k != $primary )
    		{
    			$list[$k] = $v;
    			$query .= " $k = :$k, ";
			}
    	}
    	$query = preg_replace( "/, $/", "", $query );
    	$query .= " WHERE $primary = :id";
    	$stmt = ActiveRecordPdo::instance()->prepare( $query );
    	foreach( $list as $k => $v )
    	{
			$stmt->bindParam( ":" . $k, $list[$k] );
		}	;
		$stmt->bindParam( ":$primary", $obj->id );
		$ret = $stmt->execute();
		return $ret;
    }

	/**
	 *	Delete a single ActiveRecord object
	 *
	 *	@todo Error checking 
	 *
	 *	@access public
	 *	@return bool If the AR was deleted
	 *	@param ActiveRecord object
	 */
    public function delete( ActiveRecord $obj )
    {
    	$primary = $obj->primarykey;
    	$query = 'DELETE FROM ' . $this->table . ' WHERE ';
    	$query .= $primary  . ' = ' . $obj->{$primary};
    	return ActiveRecordPdo::instance()->exec( $query );
    }
	
    /**
     *	Add a field to the class, done at first run, or when
     *	the AR cache has expired
     *
     *	@access public
     *	@return void
     *	@param string The name of the field
     *	@param array The configuration for the $name field
  	 */   
  	protected function addField( $name, $config )
    {
        $this->fields[$name] = $config;
    }
    
    /**
     *	Register a adapter for the AR.
     *
     *	@access public
     *	@return void
     *	@param string The table name
     *	@param object ActiveRecord_Adapter 
     */
	static public function registerAdapter( $table, $adapter )
    {
        self::$adapters[$table] = $adapter;
    }
    
	/**
	 *	Get the adapter for a given table ( there never exists more
	 *	than a single adapter for a single table.
	 *
	 *	@access public
	 *	@return object ActiveRecord_Adapter
	 *	@param string The table name to fetch a adapter for
	 */
    static public function instanceFor( $table )
    {
        if( isset( self::$adapters[$table] ) )
        {
            return self::$adapters[$table];
        }
        return false;
    }
}
?>
