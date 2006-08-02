<?php
/**
 * 	@package wx.php.core
 */

/**
	* 	@package wx.php.core
  *   The absolute base class. This will instantiate all of the other 
  *   four base classes, ConfigBase, ModelBase, ControllerBase, ViewBase.
  *
  *   Methods Available to sub-classes.....
  *   fetch_config()
  *   process_exception()
  *   process_error()
  *   inspect()
  */
class ApplicationBase
{
/**
	*	 Stores a reference to the config object
	*  @access private
	*  @var reference
	*/
	private 	$config_object=null;
/**
	*		Stores the name of the controller to be run.
	*		@access protected
	*		@var 		string
	*/
	protected $controller;
	/**
	 *	Stores an array of actions.
	 *	Initially this is all sections of the url
	 *	As the controller and action are loaded this is
	 *	then reduced to the remaining part of the url
	 *	@access protected
	 *	@var 		array
	 */
	protected $actions=array();
	/**
	 *	Stores the name of the action to be run.
	 *	@access protected
	 *	@var 		string
	 */
	protected $action;
	/**
	 *	Stores a reference to the controller object
	 *	This is then used to run the action within the controller.
	 *	@access private
	 *	@var reference
	 */
	protected $controller_object;
	/**
	 *	Stores a string containing the processed HTML for the view.
	 *	@access protected
	 *	@var 		string
	 */
	protected $view_html;
	/**
	 *	Stores a string containing the processed HTML for the layout.
	 *	@access protected
	 *	@var 		string
	 */
	protected $layout_html;
	
	
	/**
	 *	Sets up the application and orchestrates progression.
	 *  @access public
   *  @return void
   */
	function __construct()
	{
    set_exception_handler(array($this, 'process_exception'));    
    set_error_handler(array($this, 'process_error'), 259);
		$this->load_config();				
		$this->copy_javascript();		
		//$this->mysql_db_backup(); 
    // Clean User Input
    $filter=new InputFilter(array(), array(), 1,1);
    $_POST=$filter->process($_POST);
    $_GET=$filter->process($_GET);
    $this->controller_object=$this->load_controller(); 	
    $this->create_page($this->controller_object);
  }

	/**
	 *	Instantiates a config object and constructs the route.
	 *  @access private
   *  @return void
   */
	private function load_config() 
	{
		if(!$this->config_object) { $this->config_object=new ConfigBase; }
		$route=new WXroute;		
		$this->controller=$route->make_controller_route();		
		$this->actions=$route->read_actions();		
    }
	
	/**
	 *	Looks up a configuration value from the loaded
	 *	config object. Returns array of values.
	 *  @access protected
   *  @return array
   */
	protected function fetch_config($config)
	{
		$this->load_config();
		if($this->config_object)
		{
		return $this->config_object->return_config($config);	
		} else {
			return false;
		}
	}
		
	/**
	 *	Maps the controller to the controller file.
	 *	Decides on the action to run: Either the named action
	 *	if passed, an 'index' action or finally a fallback action
	 *	named 'missing_action'.
	 *	Returns a reference to the controller object.
	 *  @access private
   *  @return obj
   */	
	private function load_controller()
	{
	  if(class_exists($this->controller."_controller", false)) { 		
			$controller=$this->controller."_controller";
		} else {
			$controller=ucfirst($this->controller)."Controller";
		}
	  $this->action=$this->actions[0];
	  array_shift($this->actions);
	  $final_route=$this->actions;
	  if(strlen($this->action)<1) { $this->action="index"; }
	  try
	  	{
	    	$cnt=new $controller();
	      $cnt->set_routes($final_route);
	      $cnt->set_action($this->action);
	      $cnt->controller_global();
	     }
	   catch(Exception $e) 
        {
            $this->process_exception($e);
        }
		$cnt->before_action($cnt->action);
		$cnt->{$cnt->action}();
		$cnt->filter_routes();
		$cnt->after_action($cnt->action);
		return $cnt;   
	}

	
	/**
	 *	Constructs the Output.
	 *	Uses PHPTAL to parse templates, Inserts view html into the defined slot. 
	 *	Also prepends a doctype and page head to the layout.
	 *  @access private
   *  @return void
   */	
	private function create_page($cnt)
	{
		if($this->fetch_config("templating")!="php") {
			$messages=new MessageTrigger();
			$tpl=new PHPTAL();
			$tpl->addTrigger('message_insert', $messages);
			$tpl->stripComments(true);
		} else {
			$tpl=new WXTemplate;
			
		}
		
		$tpl->urlid=$cnt->action;
  	$use_layout=$cnt->use_layout;
    foreach(get_object_vars($cnt) as $var=>$val) {
      $tpl->{$var}=$val;
    }

		if(!$cnt->use_view) { 
			$use_view=$this->action; 
		} else {
			$use_view=$cnt->use_view;
		}
		if(strpos($use_view, '/')) { 
			$view_path="$use_view".".html"."/view"; 
		} else { 
			$view_path=$this->controller."/".$use_view.".html"."/view"; 
		}
		$tpl->view_path=$view_path;
    if($cnt->use_layout) {
			$tpl->layout_path="layouts/".$use_layout.".html/layout";
	  	$tpl->setTemplate(FRAMEWORK_DIR.'lib_core/page_head.html');
    } else {
			$tpl->setTemplate(FRAMEWORK_DIR.'lib_core/empty_page.html');
		}
    try {
			$page_output=$tpl->execute();
			Session::start();
      echo $page_output;
			if($_GET['route']  == '/index') {
				Session::set('referrer', $_GET['route']);
			} else {
				Session::set('referrer', "/".$_GET['route']);
			}	
 		} catch(Exception $e) {
        $this->process_exception($e);
    }
	}
	
	/**
	 *	The production environment exception handler.
	 *	Emails the trace and prints a generic user message to the screen. 
	 *  @access public
   *  @return void
   */	
	public function process_exception_prod($e)
	{
		$trace.="<font face=\"verdana, arial, helvetica, sans-serif\">\n";
		$trace.="<h1>Application Error</h1>\n";
		$trace.="Oops, looks like there's been an error. Give it a few minutes and try again.";
		$trace.="</font>\n";
		echo $trace;
		$message=strip_tags($this->get_trace($e));
		error_log($message);
		//mail("ross@webxpress.com", "Application Error on production server", $message);
		if($this->fetch_config("debug")) { echo $message; }
		exit();
	}
	/**
	 *	The development environment exception handler.
	 *	Prints the stack trace to the screen. 
	 *  @access public
   *  @return void
   */	
	public function process_exception($e)
	{
		if($this->fetch_config('environment')=='production') {
	    	$this->process_exception_prod($e); exit;
		}
		echo $this->get_trace($e);        
		exit();
	}
	/**
	 *	Converts the stack trace into a single html string.
	 *  @access public
   *  @return string
   */	
	public function get_trace($e) {
		$trace.="<font face=\"verdana, arial, helvetica, sans-serif\">\n";
    	$trace.="<h1>Application Error</h1>\n";
    	$trace.="<p>{$e->getMessage()}</p>\n";
    	$trace.="<pre style=\"background-color: #eee;padding:10px;font-size: 11px;\">";
    	$trace.="<code>{$e->getTraceAsString()}</code></pre>\n";
    	$trace.="<pre style=\"background-color: #eee;padding:10px;font-size: 11px; margin-top:5px;\">";
    	$trace.="<code>{$e->getFile()}\nLine: {$e->getLine()}</code></pre>\n";
    	$trace.="</font>\n";
		return $trace;
	}
	
	/**
	 *	Maps errors to the standard exception handler.
	 *  @access public
   *  @return void
   */	
	 public function process_error($errno, $errstr, $errfile, $errline) 
   {
     throw new Exception($errstr, $errno);
   }

	/**
	 *	Echos a formatted array to screen.
	 *	Superceded by inspect
	 *  @access protected
   *  @return void
	 *	@deprecated
   */	
	protected function inspect_array($array) {
		$this->inspect($array);
	}
	/**
	 *	Echos a formatted array to screen.
	 *  @access protected
   *  @return void
   */	
	public function inspect($array)
	{
	   echo "<pre>"; print_r($array); echo "</pre>"; 
	}
	

	/**
	 *	Copies javascript files from the javascript library
	 * 	into the runtime javascript folder.
	 *	There is a time restriction in place, the copies will only be performed
	 * periodically to improve performance.
	 *  @access private
   *  @return void
   */	
	private function copy_javascript() {
		 $fileArray=scandir(FRAMEWORK_DIR.'lib_core/javascript');
	   foreach($fileArray as $file)
        {
           if(preg_match("/^[a-zA-Z0-9_-\S]+\.js/",$file, $match))
           { $includeArray[]=$match[0]; }
        }
			$destdir=APP_DIR.'public/javascript/lib/';
			foreach($includeArray as $scriptfile) {
				$copyfile=true;
				if(file_exists($destdir.$scriptfile)) {
					$modtime=filemtime($destdir.$scriptfile);
					if($modtime>=(time() - 2592000 ) ) { $copyfile=false; $success=true; }
				}
				if($copyfile) { $success=copy(FRAMEWORK_DIR.'lib_core/javascript/'.$scriptfile, $destdir.$scriptfile); }
				if(!$success) { throw new Exception("Couldn't copy the javascript files"); }
				$success=false;
			}
	}
	
	/**
	 *	Includes an entire directory of php files.
	 *	This method is now handled by the AutoLoader class.
	 *	Left here for backwards compatibility.
	 *  @access public
   *  @return bool
	 *	@deprecated
   */	
	public function find_include_php($dir) {
		AutoLoader::include_dir($dir);
		return true;
	}

	/**
	 *	Writes a dump of the database to the app/model folder.
	 *	Occurs only in development environment and for mysql db only.
	 *	@todo Implement for other databases.
	 *  @access private
   *  @return bool
   */	
	private function mysql_db_backup() {
		if($this->fetch_config('environment')=='development') {
			$db=$this->fetch_config('db');
			if($db['dbtype']=="mysql") {
				$database=$db['database']; $user=$db['username']; $pass=$db['password'];
				$file=APP_DIR.'model/'.$database.'.sql';
				if(is_writable($file)) {
					$modtime=filemtime($file);
					if($modtime>=(time() - 1800 ) ) { return false; }
					unlink($file);
				}
				else { throw new Exception("Couldn't backup database - Check your app/model dir is writable"); }
 				$backup="/usr/local/mysql5/bin/mysqldump -u{$user} -p{$pass} $database  > $file";
				$result=passthru($backup);
				if($result) { return true; }
			}
		}
		return false;
	}

}


?>
