<?php
/**
 * @package wx.php.db
 */

/**
 * @package wx.php.db
 */
class ActiveRecord_Adapter_Mysql extends ActiveRecord_Adapter
{
	const BEGIN = "BEGIN";
	const ROLLBACK = "ROLLBACK";
	const COMMIT = "COMMIT";
	
	/**
	 *	Constructor, loads the table structure for the current
	 *	table and sets this on the parent.
	 *
	 *	@access public
	 *	@return void
	 *	@param string The table name for the class
	 */
    public function __construct( $table )
    {
        parent::__construct( $table );
        $this->loadStructure();
    }
    // }}}
    
    // {{{{ private function loadStructure()
    /**
     *	Private helper function to load the structure of the
     *	table from mysql.
     *
     *	@access private
     *	@return void
     */
    private function loadStructure()
    {
    	$query = 'DESCRIBE `' . $this->table.'`';
        foreach( ActiveRecordPdo::instance()->query( $query, PDO::FETCH_ASSOC ) as $row )
        {
        	$tmp = array();
        	$tmp['name'] = $row['Field'];
        	$tmp['default'] = $row['Default'];
        	preg_match( '/^([a-zA-Z0-9]+)\(([0-9]+)\)/', $row['Type'], $matches );
        	$tmp['max'] = null;
        	$tmp['type'] = $row['Type'];
        	if( isset( $matches[2] ) && 
        		isset( $matches[1] ) )
        	{
        		$tmp['max'] = $matches[2];
        		$tmp['type'] = $matches[1];
        	}
            $this->addField( $row['Field'], $tmp );
        }   
    }
    // }}}

	// {{{ public function lastId
	/**
	 *	Get a SQL statement to get the last ID on a given table
	 *
	 *	@access public
	 *	@return string
	 */
	public function lastId()
	{
		return 'SELECT LAST_INSERT_ID() as id FROM ' . $this->table;
	}

	/**
	 *	Mysql needs to implement its own sqlFindAll() since
	 *	it is a bit picky...
	 *
	 *	@access public
	 *	@return string Final SQl statement
	 *	@param array SQL modifiers
	 */
    public function sqlFindAll( $args = null, ActiveRecord $obj )
    {
			$table=new $this->table;
    	$primary = $obj->primarykey;			
    	$query = "SELECT * FROM " . $this->table;
    	$order = " ORDER BY $primary";
		$limit = "";
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
						$order = $order.' '.$v;
						break;
					case 'limit':
						$limit = ' LIMIT '.$v.$limit;
						break;
					case 'offset':
						$limit = $limit.', '.$v;
				}
			}
		}
		return $query . $order . $limit;
	}

}
?>
