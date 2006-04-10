<?php
/**
 * @package Wx.php_DB
 */

class ActiveRecord_Adapter_Sqlite extends ActiveRecord_Adapter
{

	// {{{ class constants
	const BEGIN = 'BEGIN';
	const COMMIT = 'COMMIT';
	const ROLLBACK = 'ROLLBACK';
	// }}}
	
	// {{{ public function __construct
	/**
	 *	Constructor, loads the structure of the table
	 *
	 *	@access public
	 *	@return object ActiveRecord_Adapter_Sqlite
	 *	@param string The table to work on
	 */
    public function __construct( $table )
    {
        parent::__construct( $table );
        $this->loadStructure();
    }
	// }}}
   
   	//{{{ private function loadStructure
   	/**
   	 *	Private helper function to load the structure
   	 *	of a sqlite table into the adapter
   	 *
   	 *	@access private
   	 *	@return void
   	 */
    private function loadStructure()
    {
    	$query = 'PRAGMA table_info(' . $this->table . ')';
		$stmt = ActiveRecordPdo::instance()->prepare( $query );
		if( $stmt->execute() )
		{		
			while(  $row = $stmt->fetch( PDO_FETCH_ASSOC) )
			{
				$tmp = array();
				$tmp['name'] = $row['name'];
				$tmp['default'] = $row['dflt_value'];
				preg_match( '/^([a-zA-Z0-9]+)\(([0-9]+)\)/', $row['type'], $matches );
				$tmp['max'] = null;
				$tmp['type'] = $row['type'];
				if( isset( $matches[2] ) && isset( $matches[1] ) )
				{
					$tmp['max'] = $matches[2];
					$tmp['type'] = $matches[1];
				}
				$this->addField( $row['name'], $tmp );
			}   
		}
    }
	// }}}

	// {{{ public function sqlFindAll( $args = null )
	/**
	 *	Sqlite implementation of the sqlFindAll() function
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
						$order .= ' '.$v;
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
