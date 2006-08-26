<?php
/**
	*  @package wx.php.db
	*  File contains three Active Record classes.
	*
	*/

/**
 *  ActiveRecordPdo IS the class that gives us the PDO object
 *  via the static "instance()" function.
 *
 *  This is never needed other than in the ActiveRecord/adapters, unless
 *  you want to explicitly use a PDO object in any content objects.
 *
 *  @package wx.php.db
 *  @license PHP
 */
class ActiveRecordPdo
{
   protected static $default_pdo = null;
   protected static $DBAdapter=null;
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
    
    static function setDBAdapter($adapter)
    {
       self::$DBAdapter= $adapter;
    }
    
     static function &getDBAdapter()
     {
         return self::$DBAdapter;
     }
	/**
	 *	
	 *	@access public
	 *	@return object PDO
	 */
    static public function instance()
    {
        $db=self::getDefaultPDO();
        return $db;
    }
}   

/**
 *  The main class for the ActiveRecord implementation.
 *  <p>
 *  The goal of ActiveRecord is to have a single base class
 *  that will deal with everything regarding retrieving, storing,
 *  updating etc. of a datatype.
 *  </p>
 *  <p>
 *  More about how ActiveRecord works can be found in the doc/ directory
 *  in the distribution.
 *  </p>
 *
 *  @package wx.php.db
 *  @license PHP
 */
abstract class ActiveRecord implements IteratorAggregate
{
    // {{{ protected variables
    /**
     *  The table corresponding to the current class.
     *  This might be "users", "blog_entry" or whatever.
     *  @access protected
     *  @var string
     */ 
    protected $table;
    /**
     *  Holds the ActiveRecord_Adapter class that provides
     *  database specific fetures for ActiveRecord.
     *  @access protected
     *  @var object ActiveRecord_Adapter
     */
    protected $adapter;
    /**
     *  Holds a instance of the ActiveRecordPdo object for the
     *  current content object.
     *  @access protected
     *  @var object ActiveRecordPdo
     */
    protected $pdo;
    /**
     *  Array of values that holds all the fields from 
     *  the current table. These are accessed via the __get
     *  and __set functions on the content object.
     *  @access protected
     *  @var array
     */
    protected $values;
    /**
     *  When initializing a new object that extends ActiveRecord
     *  you can specify a custom value that __get() will look
     *  for if it can't find it in $values.
     *  Example in constructor:
     *  $this->addValue( "fullname", ":firstname, :lastname" );
     *  and access this via $users->fullname
     *  @access protected
     *  @var array
     */
    protected $customValues;
    /**
     *  If the object has changed, and hence needs to be stored.
     *  @access protected
     *  @var bool
     */
    protected $changed;
    /**
     *  What tables do we relate to
     *  @access protected
     *  @var array
     */
    protected $hasmany;    
    /**
     *  In AR the primary key is pre-defined to be "id", this
     *  means that your table needs to have a "id" field that is
     *  the primary, unique, key in the table. 
     *	You can change this by overriding in the extending class.
		 *	  protected $primarykey='another_id';
     *  @access public
     *  @var string
     */
    public $primarykey = 'id';    
    /**
     *  The constructor of ActiveRecord is not needed to call if the
     *  extending class is named the same as the table you want to use.
     *  Example: table=users
     *  <pre>
     *  class Users extends ActiveRecord{...}
     *  $users = new Users();
     *  foreach( $users->find( array( 1,2,3,4,5 ) ) as $user ) {
     *      echo $user->username . "<br />";
     *  }
     *  </pre>
     *
     *  ActiveRecord assumes that your table is <em>all lower case</em> and
     *  have a primary key named <em>id</em>, this, however, can be changed just
     *  as you can change the table name in the constructor with a Users::primaryKey( "uid" );
     *  call before you construct the new object.
     *
     *  @access public
     *  @return void
     *  @param string Table name that this class works on, defaults to
     *      the class name, lowercase.
     */
    public function __construct(  $table=null )
    {
        define ('AR_BASE', dirname(__FILE__));
        define ('AR_ADAPTER_CACHE', CACHE_DIR);
        define ('AR_ADAPTER_CACHE_LIFETIME', "360");
        define ('AR_ADAPTER', ActiveRecordPDO::getDBAdapter());
        if( !$table )
        {
            $table = strtolower( get_class( $this ) );
        }
        $this->table = $table;
        $this->pdo = ActiveRecordPdo::instance();
        // We register just one adapter for each table.
        // This way we don't have to load the adapter for that table over
        // and over again.
        // With caching turned on we can get a 50% speedup       
        $adapterpath = AR_BASE."/adapter/" . AR_ADAPTER . ".php";
        $cachefile = AR_ADAPTER_CACHE . '/' . AR_ADAPTER . '.' . $this->table . '.adapter.cache';
        if( include_once( $adapterpath ) )
        {
            if( defined( 'AR_ADAPTER_CACHE' ) && defined( 'AR_ADAPTER_CACHE_LIFETIME' ) )
            {
                if( is_file( $cachefile ) && 
                    filemtime( $cachefile ) > ( time() - AR_ADAPTER_CACHE_LIFETIME ) )
                {
                    $adapter = unserialize( file_get_contents( $cachefile ) );
                    ActiveRecord_Adapter::registerAdapter( $this->table, $adapter );
                }
            } 
            if(!ActiveRecord_Adapter::instanceFor( $this->table ) )
            {
                $a = "ActiveRecord_Adapter_" . AR_ADAPTER;
                $adapter = new $a( $this->table );
                if( $cachefile )
                {
                    file_put_contents( $cachefile, serialize( $adapter ) );
                }
                ActiveRecord_Adapter::registerAdapter( $this->table, $adapter ); 
            }
        }
        else
        {
            throw new exception( "Adapter: ActiveRecord_Adapter_" . AR_ADAPTER . " does not exist!" );
        }       
    
        $this->adapter = ActiveRecord_Adapter::instanceFor( $this->table );
        $this->changed = false;   
        $this->hasmany = array();
        
    }
    // }}}
    
    // {{{ public function find( $id )
    /**
     *  Find takes care of finding one, or many, instances
     *  of the current content object.
     *  Example:
     *  <pre>
     *  class User extends ActiveRecord{}
     *  $user = new User();
     *  $me =  $user->find( 1 );
     *  echo $me->username
     *  </pre>
     *  The function also provides the functionality to get content objects
     *  in a range:
     *  <pre>
     *  $user = new User();
     *  $users = $user->find( array( 1,2,3,4,5 ) );
     *  </pre>
     *  It also supports finding objects in a range:
     *  <pre>
     *  $user = new User();
     *  $users = $user->find( '1...50' ); //  Note the string
     *  // Or:
     *  $users = $user->find( range( 1, 50 ) );
     *  </pre>
     * 
     *  @access public
     *  @return mixed A single object, or a array of multiple content objects
     *  @param mixed A integer, array or NULL if nothing was found
     *  @param array SQL modifiers
     */
    public function find( $id, $args = NULL )
    {
        if( is_array( $id ) || 
        	strstr( $id, '...' ) )
        {
            return $this->findMany( $id, $args );
        }
        $stmt = $this->pdo->prepare( 
            $this->adapter->sqlBasicSelect( $this->primarykey )
        );
        $stmt->bindParam( ':' . $this->primarykey, $id );
        return $this->findOne( $stmt );
    }
    // }}}
    
    // {{{ public function findOne( PDOStatement $stmt )
    /**
     *  Helper function for the internal functions, but also a public
     *  function if you need to find a single object from a PDOStatement
     *  object.
     *
     *  @access public
     *  @return object A instance of self, cloned with the values from
     *      the PDOStatement result.
     *	@param object PDOStatement
     */
    public function findOne( PDOStatement $stmt )
    {
        $stmt->execute();
        if( $row = $stmt->fetch( PDO::FETCH_ASSOC ) )
        {
            return $this->cloneSelf( $row );
        }
        return null;
    }
    // }}}
    
    // {{{ public function findMany( $arr )
    /** 
     *  findMany() finds many object of the current content object type.
     *  This is achieved by selecting from a range of 
     *  ID's based on the objects primary key, and modified by the $args argument
     *  that can controll how the result is returned.
     *
     *  Example:
     *  <pre>
     *  $user = new User();
     *  $users = $user->find( array( 1,2,3,4,5 ) );
     *  // Or:
     *  $users = $user->find( '1...10' );
     *  </pre>
     *  The function also takes a optional second argument that is passed
     *  to the adapter for parsing, this can be the order of the output etc.
     *  The default ordering of the findMany() function is by the primary key
     *  set on the current object, so a User object with the primary key would
     *  be ordered by the primary key 'id'.
     *  You can override this by doing the following:
     *  <pre>
     *  $user = new User();
     *  $users = $user->find( '1...10', array( 'order' => 'username', 'direction' => 'DESC' )
     *  </pre>
     *
     *  @access public
     *  @return array Array of content objects

     *  @param array Array of ID's to choose from, these are chosen based on the
     *      primary key on the object
     *  @param array Optional options array that rules how the result is sent
     *      back to the caller ( see the adapter )
     */
    public function findMany( $arg, $args = NULL )
    {
        $ret = array();
        if( is_object( $arg ) )
        {
            $stmt = $arg;
        }
        else
        {
        	// Deal with $user->find( '1...10' ) queries
            if( !is_array( $arg ) && 
            	strstr( $arg, '...' ) )
            {
                $tmp = explode( '...', $arg );
                $arg = range( $tmp[0], $tmp[1] );
            }
            $query = $this->adapter->sqlBasicSelect( 
                $this->primarykey, $args, $arg 
            );
            $stmt = $this->pdo->prepare( $query );
        }
        if( $stmt->execute() )
        {
        	while( $row = $stmt->fetch( PDO::FETCH_ASSOC ) )
            {
                $ret[] = $this->cloneSelf( $row );
            }
        }
        return $ret;
    }    
    /**
     *	Find <em>all</em> entries for the current table.
     *	This will return a <em>large</em> resultset depending
     *	on your table!
     *
     *	@access public
     *	@return array
     *	@param array SQL modifiers
     */
    public function findAll( $args = null )
    {
    	$query = $this->adapter->sqlFindAll( $args, $this );
    	$stmt = $this->pdo->prepare( $query );
    	return $this->findMany( $stmt );
    }    
    /**
     *	Execute a raw SQL query.
     *	Make sure you know what you pass in as argument here!
     *
     *	@access public
     *	@return array Of elements found
     *	@param string A SQL query to execute
     */
    public function findBySql( $sql )
    {
    	$stmt = $this->pdo->prepare( $sql );
    	$ret= $this->findMany( $stmt );
			return $ret;
    }    
    /**
     *  Counts the number of records that are in the current table.
     *
     *  @access public
     *  @return integer The number of records on the table
     */
    public function count( )
    {
        $stmt = $this->pdo->prepare(
            $this->adapter->sqlCount( )
        );
        if( $stmt->execute() )
        {
            $row = $stmt->fetch( PDO::FETCH_ASSOC );
            return $row['count'];
        }
        return 0;
    }
    
    /**
     *  Whenever a call to <em>find()</em>, <em>findMany</em> etc. is done
     *  each row returned from the database is returned through this function,
     *  it will clone the current object and build it with the values from
     *  the resultset. This way you will retrieve a new object for each
     *  row that is identical with the current object.
     *
     *  @access protected
     *  @return object A instance of the current object
     *  @param array Array of values corresponding to the fields in the
     *      table for the current object
     */
    protected function cloneSelf( $values )
    {
        $ret = clone $this;
        $ret->build( $values, false );
        return $ret;
    }
    
    /**
     *  The ActiveRecord object is overloaded with the __get() and
     *  __set() functions, the values that you access through this are
     *  the fields from the database.
     *	Some times you want to populate a object from a array ( a $_POST array
     *	or from othere sources ).
     *
     *  @access public
     *  @return void
     *  @param array Array of fields and their values
     *  @param bool If the $arr param comes from the database, if this is
     *      set to TRUE the changed status of the object will <em>not</em> 
     *      change
     */
    public function build( $arr, $change = true )
    {
    	if( !is_array( $arr ) )
    	{
    		return;
    	}
        foreach( $arr as $k => $v )
        {
            $this->values[$k] = $v;
        }
        if( $change )
        {
            $this->changed = true;
        }
    }
    
	/**
	 *	Set a hasMany relationship.
	 *	This is still in a early version, it works like this:
	 *	<code>
	 *	class User extends ActiveRecord{
	 *		public function __construct() {
	 *			$this->hasMany( "blogentry:id" );
	 *		}
	 *	}
	 *	foreach( $user->blogentry as $k=>$v ) {
	 *		echo $v->blogtitle;
	 *	}
	 *	</code>
	 *	The <em>blogentries</em> can <em>not</em> crash with the 
	 *	table fields for the current object!
	 *	
	 *	@access public
	 *	@return void
	 *	@param string The table to add relationship to
	 */
    public function hasMany( $table )
    {
        $id = null;
        if( strpos( $table, ":" ) )
        {
            $many = explode( ':', $table );
            $table = $many[0];
            $id = $many[1];
        }
        $this->hasmany[$table] = array( 'table' => $table, 'primary' => $id );
    }
	/**
	 *	Get a collection of hasMany relationship objects
	 *
	 *	@access public
	 *	@return array Array of objects
	 *	@param string The model to fetch objects of
	 */
    public function getMany( $key )
    {
    	$ret = array();
        $obj = new $key();
        $primary = $this->primarykey;
        if( isset( $this->hasmany[strtolower($key)]['primary'] ) )
        {
            $primary = $this->hasmany[strtolower($key)]['primary'];
        }
        $args = array( 'key' => $primary );   
        $adapter = ActiveRecord_Adapter::instanceFor( strtolower($key) );
        $query = $adapter->sqlBasicSelect(
            $primary, $args
        );
        $stmt = $this->pdo->prepare( $query );
        $stmt->bindParam( ':' . $primary, $this->values[$this->primarykey] );
        return $obj->findMany( $stmt, $args );
    }
    
    /**
     *  Saves the current object back to the table.
     *
     *  This will differentiate between a new object and a *old* object
     *  that needs to be updated.
     *
     *  @access public
     *  @return bool If the update was successfull or not
     */
    public function save()
    {
    	$save = new ActiveRecord_Saver( 
    		$this,
    		$this->adapter,
    		$this->table
    	);
		$i = $save->save();
		if($i !== 0)
		{
			$this->id = $i;
			return $i;
		}
    	return false;
    }    
	/**
	*Return the Id of the last inserted row
	*
	* @access public
	* @return int 
	*/
	public function last_insert_id()
	{
		$result = $this->findBySql("SELECT MAX(".$this->primarykey.") as id FROM ".$this->table);
		$result = $result[0];
		return $result->id;
	}
    
    /**
     *  Delete the content object
     *
     *  @access public
     *  @return bool If the object was deleted or not
     */
    public function delete()
    {
		$del = new ActiveRecord_Saver(
			$this,
			$this->adapter,
			$this->table
		);
		return $del->delete();
    }
    
    /**
     *  The addValue() function makes it possible to add a custom object 
     *  variable at run time:
     *  <pre>
     *  class User extends ActiveRecord {
     *      public function __construct() {
     *          $this->addValue( "fullname", ":fname, :lname" );
     *          parent::__construct();
     *      }
     *  }
     *  $user = new User();
     *  $me = $user->find( 1 );
     *  echo $me->fullname;
     *  </pre>
     *  The second parameter is a string where each field name is 
     *  prefixed with <em>:</em>, the variable <em>fullname</em> is then
     *  built by getting the "fname" and "lname" from the values of the object.
     *
     *  @access public
     *  @return void
     *  @param string The name of the new variable
     *  @param string The value that should be generated by calling $name on
     *      the object
     */
    public function addValue( $name, $str )
    {
        $this->customValues[$name] = $str;
    }
 
	/**
	 *	Get the state of the object, has it been changed?
	 *	The state changes if you use __set() or the second param
	 *	to build(
	 */
    public function isChanged()
    {
    	$k = $this->primarykey;
    	if( !$this->$k )
    	{
    		return false;
		}
    	return $this->changed;
    }

    /**
     *  Overrides the __get() function for the object to be able
     *  to retrieve the field values.
     *  This also returns the value of what you set with addValue()
     *
     *  @access public
     *  @return mixed The value of the key
     *  @param string The field key to retrieve, or the custom value
     *      built with addValue()
     */
    public function __get( $k )
    {
        if( isset( $this->values[$k] ) )
        {
            return $this->values[$k];
        }
        if( isset( $this->customValues[$k] ) )
        {
            preg_match_all( "/(:[a-z]+)/", $this->customValues[$k], $matches );
            $ret = $this->customValues[$k];
            foreach( $matches[0] as $k => $v )
            {
                $ret = str_replace( $v, $this->values[str_replace( ':', '', $v )], $ret ); 
            }
            return $ret;
        }
        if( isset( $this->hasmany[$k] ) )
        {
        	return $this->getMany( $k );
        }
        return null;
    }
        /**
     *  Set a value on the object, this must be a value that corresponds
     *  to the fields for the current table.
     *
     *  @access public
     *  @return void
     *  @param string The key corresponding to a valid table field
     *  @param mixed The value of the key
     */
    public function __set( $k, $v )
    {
        if( $this->adapter->validField( $k ) )
        {
            $this->values[$k] = $v;
            $this->changed = true;
        }
    }
    
    /**
     *  The __call() function makes it possible to call custom functions
     *  on the object to make the retrieving of objects more verbose.
     *  <pre>
     *  $user = new User();
     *  $me = $user->findByUsername( 'test' );
     *  echo $me->username;
     *  </pre>
     *  The above example will retrieve the object where the username
     *  is "test". It is important that whatever follows the "findBy" is
     *  a valid field name on the table you are working on.
     *
     *  Also you can use camelised text as the table is lowercased before testing.
     *
     *  You can also pass in a array of SQL modification statements:
     *  <pre>
     *  $user = new User();
     *  $norwegians = $user->findByCategory( 
     *      '2', 
     *      array( 'order' => 'username', 'direction' => 'DESC' )
     *  );
	 * 	</pre>
	 *
     *  @access public
     *  @return mixed A single object if only one is found, an array of objects if more is found.
     *  @param string The function name called
     *  @param array Arguments to the list
     */
    public function __call( $func, $args )
    {
        if( count( $args ) > 1 ) 
        {
            $many = true;
        }
        $key = 'id';
        if( count( $args ) > 0 )
        {
            $key = $args[0];
        }
        $args = array_pop( $args );
        $what = strtolower(substr( $func, 6 ));
        if( $field = $this->adapter->validField( $what ) )
        {
            $query = $this->adapter->sqlBasicSelect( $what, $args );
            $stmt = $this->pdo->prepare( $query );
            $stmt->bindParam( ':' . $what, $key );
            $ret = $this->findMany( $stmt );
            return $ret;
        }
        return null;
    }

	/**
	 *	Get a iterator to iterate over all the fields that
	 *	the current model have
	 *
	 *	@access public
	 *	@return object ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator(
			new ArrayObject( 
				$this->values 
			)
		);
	}

	/**
	 *	Iterator over all the fields available for the current model
	 *
	 *	@access public
	 *	@return ArrayIterator
	 */
	public function fieldIterator()
	{
		return $this->adapter->getIterator();
	}
	
    /**
     *  The primary key on a table ( that is used to select/update/insert on )
     *  is default <em>id</em>, but: this can be changed before initializing the
     *  object by calling this static function on the user object.
     *  <pre>
     *  class User extends ActiveRecord
     *  protected $primarykey="user_id";
     *  </pre>
     *  That way you can choose what is the primary key for your usage, and you're
     *  not bound by the default primary key value.
     *
     *  It's important that if you use a User object in conjunction with, say, a
     *  Blog object that this function is called, otherwise the Blog object won't 
     *  know what primary key to work with.
     *
     *  @access public
     *  @return string The primary key, if the $key param is not available
     *  @param string Optional string if you want to modify the primary key
     */
    public function primaryKey( $key = NULL )
    {
        if($key) { $this->primarykey=$key; }
				return $this->primarykey;
    }
		
		/**
     *  This method receives an array of values, adds them to the object
     *  and then automatically saves/updates the row.
     *  Acts as a shorthand for individually adding values.
     *
		 */
		public function add_row_save($array) {
			foreach($array as $k=>$v) {
				if( $this->adapter->validField( $k ) ) {
	      	$this->values[$k] = $v;
	      	$this->changed = true;
	      }
			}
			if($this->changed) { return $this->save(); }
			else { return false; }
		}
		
}

/**
	*  @package wx.php.db
	*	A helper class that wraps the ActiveRecord update/new/delete
	*	statements with ROLLBAKCK/COMMIT statemts
	*	It will execute all the logic for the AR
	*/
class ActiveRecord_Saver
{	
	/**
	 *	@access private
	 *	@var object ActiveRecord
	 */
	private $obj;
	/**
	 *	@access private
	 *	@var object ActiveRecord_Adapter
	 */
	private $adapter;
	/**
	 *	The table name we are saving to
	 *	@access private
	 *	@var string
	 */
	private $table;
	
	/**
	 *	The table object we are saving to
	 *	@access private
	 *	@var string
	 */
	private $pdo;
	
	/**
	 *	Cosntructor, just sets up all the parts needed to
	 *	save/update a object.
	 *
	 *	@access public
	 *	@return object ActiveRecord_Saver
	 *	@param object ActiveRecord
	 *	@param object ActiveRecord_Adapter
	 *	@param string The table name that is updated on
	 */
	public function __construct( ActiveRecord $obj, ActiveRecord_Adapter $a, $table )
	{
		$this->obj = $obj;
		$this->adapter = $a;
		$this->table = $table;
		$this->pdo = ActiveRecordPdo::instance();
	}
	
	/**
	 *	Save a AR object.
	 *	The function will check if the object has been changed and if 
	 *	it should update or save a new object.
	 *
	 *	@access public
	 *	@return bool If the updating/saving was successful
	 */
	public function save()
	{
		$ret = false;
		$this->begin();
		// @todo Check for objects that belong to this object!
		if( $this->obj->isChanged() )
		{
			$ret = $this->adapter->update( $this->obj );
			
		}
		else
		{
			$ret = $this->adapter->saveNew( $this->obj );
		}
		if( $ret )
		{
			$this->commit();
			return $ret;
		}
		$this->rollback();
		return false;
	}
	
	/**
	 *	Delete a single object.
	 *
	 *	@access public
	 *	@return bool If the object was deleted or not
	 */
	public function delete()
	{
		$ret = false;
		$this->begin();
		// @todo Check for objects that belong to this object!
		$ret = $this->adapter->delete( $this->obj );
		if( $ret )
		{
			$this->commit();
			return true;
		}
		$this->rollback();
		return false;
	}
	
	/**
	 *	Helper function that starts a BEGIN statement
	 *
	 *	@access public
	 *	@return void
	 */
	public function begin()
	{
		$this->pdo->exec( 
			constant( get_class( $this->adapter ) . '::BEGIN') 
		);
	}
		
	/**
	 *	Helper function that executes a COMMIT statement
	 *
	 *	@access public
	 *	@return void
	 */
	public function commit()
	{
		$this->pdo->exec( 
			constant( get_class( $this->adapter ) . '::COMMIT') 
		);
	}
	
	/**
	 *	Helper function that executes a ROLLBACK statement
	 *
	 *	@access public
	 *	@return void
	 */
	public function rollback()
	{
		$this->pdo->exec( 
			constant( get_class( $this->adapter ) . '::ROLLBACK') 
		);
	}
}




?>
