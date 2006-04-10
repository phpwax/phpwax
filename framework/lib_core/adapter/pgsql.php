<?php
/**
 * @package Wx.php_DB
 */
class ActiveRecord_Adapter_Pgsql extends ActiveRecord_Adapter
{
	
	/**
	 *	Postgres adapter for ActiveRecord
	 *
	 */
	// {{{ class constants
	const BEGIN = 'BEGIN';
	const COMMIT = 'COMMIT';
	const ROLLBACK = 'ROLLBACK';
	// }}}
	
	// {{{ public function __construct( $table )
	/**
	 *	Constructor, loads the table structure from postgres
	 *	based on the current table.
	 *
	 *	@access public
	 *	@return void
	 *	@param string The table name currently being worked on
	 */
    public function __construct( $table )
    {
        parent::__construct( $table );
        $this->loadStructure();
    }
    // }}}
    
    // {{{ private function loadStructure()
    /**
     *	Private helper function for the constructor.
     *
     *	@access private
     *	@return void
     */
    private function loadStructure()
    {
        $query = "
            SELECT 
                column_name as name, 
                column_default as default, 
                character_maximum_length as max, 
                data_type as type,
                is_nullable as nullable
            FROM information_schema.columns
            WHERE table_catalog = '" . AR_DB . "'
            AND table_name = '" . $this->table . "';
        ";
        foreach( ActiveRecordPdo::instance()->query( $query, PDO_FETCH_ASSOC ) as $row )
        {
        	if( $row["type"] == 'character varying' )
        	{
        		$row['type'] = 'varchar';
        	}
            $this->addField( $row['name'], $row );
        }
    }
    // }}}

	// {{{ public function lastID
	/**
	 *	Get a SQL statement for the last insert ID on a table
	 *
	 *	Postgres is a bit special in how it handles its sequences.
	 *	Default handling is to choose a sequence named </em>{table}_{primarykey}_seq</em>,
	 *	but: you can define a constant: <em>AR_SEQUENCE</em> instead, the function
	 *	will then use it to figure out the last insert ID
	 *
	 *	@access public
	 *	@return string 
	 */
	public function lastID()
	{
		if( defined( 'AR_SEQUENCE' ) )
		{
			$sequence = AR_SEQUENCE;
		}
		else
		{
			$sequence = $this->table . '_' . call_user_func( array( $this->table, 'primaryKey' ) ) . '_seq';
		}
		return "SELECT currval('" . $sequence . "') as id";
	}
	// }}}
	
	// {{{ public function sqlFindAll( $args = null )
	/**
	 *	Implementation of the sqlFindAll() for postgres.
	 *
	 *	@access public
	 *	@return string
	 */
    public function sqlFindAll( $args = null, ActiveRecord $obj )
    {
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
						$limit = $limit.' OFFSET '.$v;
				}
			}
		}
		return $query . $order . $limit;
	}
	// }}}

}
// }}}
?>
